using ParfumAdmin_WPF.Models;
using ParfumAdmin_WPF.Services.Interfaces;
using System;
using System.Net.Http;
using System.Text;
using System.Text.Json;

namespace ParfumAdmin_WPF.Services
{
    public class AuthService : IAuthService
    {
        private readonly HttpClient _httpClient;
        private readonly TokenStore _tokenStore;
        private readonly IAuthState _authState;

        public AuthService(HttpClient httpClient, TokenStore tokenStore, IAuthState authState)
        {
            _httpClient = httpClient;
            _tokenStore = tokenStore;
            _authState = authState;
        }

        public async Task<LoginResponse> LoginAsync(string email, string password)
        {
            var payload = new { email, password };
            var json = JsonSerializer.Serialize(payload);
            var content = new StringContent(json, Encoding.UTF8, "application/json");

            HttpResponseMessage response;
            string body;
            try
            {
                response = await _httpClient.PostAsync("login", content);
                body = await response.Content.ReadAsStringAsync();
            }
            catch (HttpRequestException)
            {
                throw new ApiException(
                    System.Net.HttpStatusCode.ServiceUnavailable,
                    "Nem sikerült elérni a szervert. Ellenőrizd az internetkapcsolatot.");
            }

            if (!response.IsSuccessStatusCode)
            {
                string userMessage = (int)response.StatusCode switch
                {
                    401 => "Hibás email cím vagy jelszó.",
                    422 => "Hiányzó vagy érvénytelen adatok.",
                    429 => "Túl sok próbálkozás. Várj egy percet, és próbáld újra.",
                    _   => $"Bejelentkezés sikertelen ({(int)response.StatusCode})."
                };
                throw new ApiException(response.StatusCode, userMessage);
            }

            var options = new JsonSerializerOptions { PropertyNameCaseInsensitive = true };
            var result = JsonSerializer.Deserialize<LoginResponse>(body, options);

            if (result == null || string.IsNullOrEmpty(result.Token))
            {
                throw new ApiException(
                    System.Net.HttpStatusCode.OK,
                    "A szerver válasza érvénytelen.");
            }

            _tokenStore.Save(result.Token);

            return result;
        }

        public void Logout()
        {
            _tokenStore.Clear();
            _authState.NotifySessionExpired();
        }

        public bool IsLoggedIn() => !string.IsNullOrEmpty(_tokenStore.Load());

        public string GetToken() => _tokenStore.Load() ?? string.Empty;
    }
}
