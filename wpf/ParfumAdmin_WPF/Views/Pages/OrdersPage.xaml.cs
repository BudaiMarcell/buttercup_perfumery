using System.ComponentModel;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Input;
using System.Windows.Media;
using ParfumAdmin_WPF.ViewModels;

namespace ParfumAdmin_WPF.Views.Pages
{
    public partial class OrdersPage : Page
    {
        private readonly OrdersViewModel _viewModel;

        public OrdersPage(OrdersViewModel viewModel)
        {
            InitializeComponent();
            _viewModel  = viewModel;
            DataContext = _viewModel;

            _viewModel.ConfirmDelete = ConfirmDeleteOrder;

            this.PreviewMouseLeftButtonDown += Page_PreviewMouseLeftButtonDown;
        }

        private bool ConfirmDeleteOrder(ParfumAdmin_WPF.Models.Order o)
        {
            var dialog = new ConfirmDialog(
                "Rendelés törlése",
                $"Biztosan törlöd a(z) #{o.Id} rendelést?")
            {
                Owner = Window.GetWindow(this)
            };
            return dialog.ShowDialog() == true;
        }

        private async void Page_Loaded(object sender, RoutedEventArgs e)
        {
            await _viewModel.LoadOrdersAsync();
        }

        private void Page_PreviewMouseLeftButtonDown(object sender, MouseButtonEventArgs e)
        {
            var src = e.OriginalSource as DependencyObject;
            if (FindParent<System.Windows.Controls.Primitives.ButtonBase>(src) != null) return;
            if (FindParent<DataGridRow>(src) == null)
                _viewModel.SelectedOrder = null;
        }

        private void DataGrid_Sorting(object sender, DataGridSortingEventArgs e)
        {
            if (e.Column.SortDirection != ListSortDirection.Descending) return;
            e.Handled = true;
            e.Column.SortDirection = null;
            CollectionViewSource.GetDefaultView(((DataGrid)sender).ItemsSource)
                                ?.SortDescriptions.Clear();
        }

        private static T? FindParent<T>(DependencyObject? child) where T : DependencyObject
        {
            while (child != null)
            {
                if (child is T t) return t;
                child = VisualTreeHelper.GetParent(child);
            }
            return null;
        }
    }
}
