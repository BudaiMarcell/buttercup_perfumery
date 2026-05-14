using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Options;
using ParfumAdmin_WPF.Models;
using ParfumAdmin_WPF.Services;
using ParfumAdmin_WPF.Services.Interfaces;
using ParfumAdmin_WPF.ViewModels;
using ParfumAdmin_WPF.Views;
using ParfumAdmin_WPF.Views.Pages;
using Serilog;
using System;
using System.IO;
using System.Net.Http;
using System.Windows;

namespace ParfumAdmin_WPF
{
    public partial class App : Application
    {
        public static IServiceProvider ServiceProvider { get; private set; } = null!;

        protected override void OnStartup(StartupEventArgs e)
        {
            base.OnStartup(e);

            // ── Global unhandled-exception nets ──────────────────────────
            // Without these, ANY throw during startup — config not found,
            // XAML parse error, DI graph problem — silently kills the
            // process with no window shown. The user sees nothing and
            // we have no log. These three handlers cover the three
            // surfaces .NET can throw on: UI thread, background thread,
            // and untracked Task.
            AppDomain.CurrentDomain.UnhandledException += (s, args) =>
                ReportFatal("AppDomain.UnhandledException", args.ExceptionObject as Exception);

            this.DispatcherUnhandledException += (s, args) =>
            {
                ReportFatal("DispatcherUnhandledException", args.Exception);
                args.Handled = true; // keep the app alive long enough to show the box
            };

            System.Threading.Tasks.TaskScheduler.UnobservedTaskException += (s, args) =>
            {
                ReportFatal("UnobservedTaskException", args.Exception);
                args.SetObserved();
            };

            // ── Configuration ─────────────────────────────────────────────
            var basePath = AppContext.BaseDirectory;
            IConfiguration config = new ConfigurationBuilder()
                .SetBasePath(basePath)
                .AddJsonFile("appsettings.json", optional: false, reloadOnChange: false)
                .AddEnvironmentVariables()
                .Build();

            var apiSection = config.GetSection("Api");
            var baseUrl = apiSection["BaseUrl"];
            if (string.IsNullOrWhiteSpace(baseUrl) ||
                !Uri.TryCreate(baseUrl, UriKind.Absolute, out var apiUri))
            {
                MessageBox.Show(
                    "Hiányzik vagy érvénytelen az Api:BaseUrl beállítás az appsettings.json fájlban.\n\n" +
                    $"Várt elérési út: {Path.Combine(basePath, "appsettings.json")}",
                    "Konfigurációs hiba", MessageBoxButton.OK, MessageBoxImage.Error);
                Shutdown(1);
                return;
            }

            // ── Logging ───────────────────────────────────────────────────
            var logPathTemplate = config["Logging:File:Path"]
                ?? "%LOCALAPPDATA%\\ParfumAdmin\\logs\\app-.log";
            var logPath = Environment.ExpandEnvironmentVariables(logPathTemplate);
            try
            {
                Directory.CreateDirectory(Path.GetDirectoryName(logPath)!);
            }
            catch
            {
            }

            Log.Logger = new LoggerConfiguration()
                .MinimumLevel.Information()
                .WriteTo.File(
                    path: logPath,
                    rollingInterval: RollingInterval.Day,
                    retainedFileCountLimit: 7,
                    shared: true,
                    outputTemplate: "{Timestamp:yyyy-MM-dd HH:mm:ss.fff} [{Level:u3}] {Message:lj}{NewLine}{Exception}")
                .CreateLogger();

            Log.Information("ParfumAdmin starting (api={ApiUri})", apiUri);

            // ── DI container ──────────────────────────────────────────────
            var services = new ServiceCollection();

            services.AddSingleton(config);
            services.Configure<ApiOptions>(apiSection);

            services.AddSingleton<TokenStore>();
            services.AddSingleton<IAuthState, AuthState>();

            services.AddTransient<AuthDelegatingHandler>();

            // ── Typed HttpClients ─────────────────────────────────────────
            services.AddHttpClient<IAuthService, AuthService>((sp, client) =>
            {
                var opts = sp.GetRequiredService<IOptions<ApiOptions>>().Value;
                client.BaseAddress = new Uri(opts.BaseUrl, UriKind.Absolute);
                client.Timeout = TimeSpan.FromSeconds(15);
                client.DefaultRequestHeaders.Add("Accept", "application/json");
                client.DefaultRequestHeaders.AcceptEncoding.ParseAdd("gzip, deflate");
            })
            .ConfigurePrimaryHttpMessageHandler(() => new SocketsHttpHandler
            {
                AutomaticDecompression = System.Net.DecompressionMethods.GZip
                                       | System.Net.DecompressionMethods.Deflate,
                PooledConnectionLifetime = TimeSpan.FromMinutes(10),
                PooledConnectionIdleTimeout = TimeSpan.FromMinutes(2),
                ConnectTimeout = TimeSpan.FromSeconds(5),
            });

            services.AddHttpClient<IApiService, ApiService>((sp, client) =>
            {
                var opts = sp.GetRequiredService<IOptions<ApiOptions>>().Value;
                client.BaseAddress = new Uri(opts.BaseUrl, UriKind.Absolute);
                client.Timeout = TimeSpan.FromSeconds(15);
                client.DefaultRequestHeaders.Add("Accept", "application/json");
                client.DefaultRequestHeaders.AcceptEncoding.ParseAdd("gzip, deflate");
            })
            .ConfigurePrimaryHttpMessageHandler(() => new SocketsHttpHandler
            {
                AutomaticDecompression = System.Net.DecompressionMethods.GZip
                                       | System.Net.DecompressionMethods.Deflate,
                PooledConnectionLifetime = TimeSpan.FromMinutes(10),
                PooledConnectionIdleTimeout = TimeSpan.FromMinutes(2),
                ConnectTimeout = TimeSpan.FromSeconds(5),
            })
            .AddHttpMessageHandler<AuthDelegatingHandler>();

            // ── ViewModels ────────────────────────────────────────────────
            services.AddTransient<LoginViewModel>();
            services.AddTransient<ProductsViewModel>();
            services.AddTransient<OrdersViewModel>();
            services.AddTransient<DashboardViewModel>();
            services.AddTransient<ProductFormViewModel>();
            services.AddTransient<CouponsViewModel>();
            services.AddTransient<CouponFormViewModel>();
            services.AddTransient<AuditLogsViewModel>();
            services.AddTransient<AnalyticsViewModel>();

            // ── Views ─────────────────────────────────────────────────────
            services.AddTransient<LoginWindow>();
            services.AddTransient<MainWindow>();
            services.AddTransient<ProductFormWindow>();
            services.AddTransient<CouponFormWindow>();

            // ── Pages ─────────────────────────────────────────────────────
            services.AddTransient<DashboardPage>();
            services.AddTransient<ProductsPage>();
            services.AddTransient<OrdersPage>();
            services.AddTransient<CouponsPage>();
            services.AddTransient<AuditLogsPage>();
            services.AddTransient<AnalyticsPage>();

            ServiceProvider = services.BuildServiceProvider();

            // ── Startup window ────────────────────────────────────────────
            var tokenStore = ServiceProvider.GetRequiredService<TokenStore>();
            var hasToken = !string.IsNullOrEmpty(tokenStore.Load());

            Window initialWindow = hasToken
                ? ServiceProvider.GetRequiredService<MainWindow>()
                : ServiceProvider.GetRequiredService<LoginWindow>();
            initialWindow.Show();
        }

        protected override void OnExit(ExitEventArgs e)
        {
            Log.CloseAndFlush();
            base.OnExit(e);
        }

        /// <summary>
        /// Show the user what blew up AND write it to a crash file. We do
        /// both because Serilog may not be initialised yet when the
        /// failure happens, and a MessageBox alone doesn't survive after
        /// the user dismisses it.
        ///
        /// The crash file lives at %LOCALAPPDATA%\ParfumAdmin\crash.log
        /// regardless of whether `Logging:File:Path` has been read; that
        /// path is hardcoded so it works even when configuration loading
        /// itself is the thing that failed.
        /// </summary>
        private static void ReportFatal(string source, Exception? ex)
        {
            var message = ex?.ToString() ?? "(no exception object)";
            var full = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] {source}\n{message}\n\n";

            try
            {
                var dir = Path.Combine(
                    Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
                    "ParfumAdmin");
                Directory.CreateDirectory(dir);
                File.AppendAllText(Path.Combine(dir, "crash.log"), full);
            }
            catch { /* last-ditch effort, don't recurse on a write failure */ }

            // Serilog may or may not have been set up. Try, then swallow.
            try { Log.Fatal(ex, "Unhandled exception in {Source}", source); } catch { }

            try
            {
                MessageBox.Show(
                    "A ParfumAdmin elindítása sikertelen.\n\n" +
                    "Hiba forrása: " + source + "\n\n" +
                    (ex?.Message ?? "(ismeretlen hiba)") + "\n\n" +
                    "A részletes hibanaplót itt találod:\n" +
                    "%LOCALAPPDATA%\\ParfumAdmin\\crash.log",
                    "Indulási hiba",
                    MessageBoxButton.OK,
                    MessageBoxImage.Error);
            }
            catch { /* if even MessageBox can't render, nothing left to do */ }
        }
    }
}
