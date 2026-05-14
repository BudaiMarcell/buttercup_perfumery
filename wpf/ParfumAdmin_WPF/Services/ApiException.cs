using System;
using System.Net;

namespace ParfumAdmin_WPF.Services
{
    public class ApiException : Exception
    {
        public HttpStatusCode StatusCode { get; }
        public string UserMessage { get; }

        public ApiException(HttpStatusCode statusCode, string userMessage)
            : base(userMessage)
        {
            StatusCode = statusCode;
            UserMessage = userMessage;
        }

        public ApiException(HttpStatusCode statusCode, string userMessage, Exception inner)
            : base(userMessage, inner)
        {
            StatusCode = statusCode;
            UserMessage = userMessage;
        }
    }
}
