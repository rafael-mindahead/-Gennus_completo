import SwiftUI

struct ContentView: View {

    var body: some View {

        TabView {

            DashboardView()
                .tabItem {
                    Label(
                        "Dashboard",
                        systemImage: "chart.bar.fill"
                    )
                }

            ProdutosView()
                .tabItem {
                    Label(
                        "Produtos",
                        systemImage: "shippingbox.fill"
                    )
                }

            ClientesView()
                .tabItem {
                    Label(
                        "Clientes",
                        systemImage: "person.2.fill"
                    )
                }

            VendasView()
                .tabItem {
                    Label(
                        "Vendas",
                        systemImage: "cart.fill"
                    )
                }
        }
    }
}
