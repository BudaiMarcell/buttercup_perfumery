using System.Windows;
using ParfumAdmin_WPF.ViewModels;

namespace ParfumAdmin_WPF.Views
{
    public partial class ProductFormWindow : Window
    {
        private readonly ProductFormViewModel _viewModel;

        public ProductFormWindow(ProductFormViewModel viewModel)
        {
            InitializeComponent();
            _viewModel = viewModel;
            DataContext = _viewModel;

            _viewModel.OnRequestClose += HandleRequestClose;
            Loaded += async (_, _) => await _viewModel.LoadCategoriesAsync();
        }

        private void HandleRequestClose(bool success)
        {
            DialogResult = success;
            Close();
        }

        protected override void OnClosed(System.EventArgs e)
        {
            _viewModel.OnRequestClose -= HandleRequestClose;
            base.OnClosed(e);
        }
    }
}
