using System;
using System.Net;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Threading;
using System.Threading.Tasks;

namespace ParfumAdmin_WPF.Services
{
    public class AuthDelegatingHandler : DelegatingHandler
    {
        private readonly TokenStore _tokenStore;
        private readonly IAuthState _authState;

        public AuthDelegatingHandler(TokenStore tokenStore, IAuthState authState)
        {
            _tokenStore = tokenStore;
            _authState = authState;
        }

        protected override async Task<HttpResponseMessage> SendAsync(
            HttpRequestMessage request, CancellationToken cancellationToken)
        {
            if (request.Headers.Authorization == null)
            {
                var token = _tokenStore.Load();
                if (!string.IsNullOrEmpty(token))
                {
                    request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", token);
                }
            }

            var response = await base.SendAsync(request, cancellationToken).ConfigureAwait(false);

            if (response.StatusCode == HttpStatusCode.Unauthorized)
            {
                _tokenStore.Clear();
                _authState.NotifySessionExpired();
            }

            return response;
        }
    }
}
