using System;

namespace ParfumAdmin_WPF.Services
{
    public interface IAuthState
    {
        event EventHandler? SessionExpired;

        void NotifySessionExpired();
    }

    public class AuthState : IAuthState
    {
        public event EventHandler? SessionExpired;

        public void NotifySessionExpired()
        {
            SessionExpired?.Invoke(this, EventArgs.Empty);
        }
    }
}
