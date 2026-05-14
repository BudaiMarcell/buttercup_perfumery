using System;
using System.ComponentModel;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Threading;
using ParfumAdmin_WPF.ViewModels;

namespace ParfumAdmin_WPF.Views.Pages
{
    public partial class DashboardPage : Page
    {
        private readonly DashboardViewModel _viewModel;
        private readonly DispatcherTimer _realtimeTimer;
        private Window? _hostWindow;

        public DashboardPage(DashboardViewModel viewModel)
        {
            InitializeComponent();
            _viewModel = viewModel;
            DataContext = _viewModel;

            _realtimeTimer = new DispatcherTimer
            {
                Interval = TimeSpan.FromSeconds(30),
            };
            _realtimeTimer.Tick += async (_, _) => await _viewModel.RefreshActiveSessionsAsync();

            Unloaded += OnUnloaded;
        }

        private async void Page_Loaded(object sender, RoutedEventArgs e)
        {
            await _viewModel.LoadDataAsync();

            _hostWindow = Window.GetWindow(this);
            if (_hostWindow != null)
            {
                _hostWindow.StateChanged += OnHostStateChanged;
            }

            if (_hostWindow?.WindowState != WindowState.Minimized)
            {
                _realtimeTimer.Start();
            }
        }

        private void OnHostStateChanged(object? sender, EventArgs e)
        {
            if (_hostWindow == null) return;

            if (_hostWindow.WindowState == WindowState.Minimized)
            {
                _realtimeTimer.Stop();
            }
            else
            {
                _realtimeTimer.Start();
            }
        }

        private void OnUnloaded(object sender, RoutedEventArgs e)
        {
            _realtimeTimer.Stop();
            if (_hostWindow != null)
            {
                _hostWindow.StateChanged -= OnHostStateChanged;
                _hostWindow = null;
            }
        }

        private void StatBox_Click(object sender, RoutedEventArgs e)
        {
            if (Window.GetWindow(this) is MainWindow main)
                main.NavigateToPage("Analytics");
        }

        private void DataGrid_Sorting(object sender, DataGridSortingEventArgs e)
        {
            if (e.Column.SortDirection != ListSortDirection.Descending) return;
            e.Handled = true;
            e.Column.SortDirection = null;
            CollectionViewSource.GetDefaultView(((DataGrid)sender).ItemsSource)
                                ?.SortDescriptions.Clear();
        }
    }
}
