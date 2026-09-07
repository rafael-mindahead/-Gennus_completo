import SwiftUI

struct ClientesView: View {

    @State private var clientes: [Cliente] = []
    @State private var carregando = true
    @State private var erro: String?

    var body: some View {

        NavigationStack {

            Group {

                if carregando {

                    ProgressView("Carregando clientes...")

                } else if let erro {

                    ContentUnavailableView(
                        "Erro",
                        systemImage: "exclamationmark.triangle",
                        description: Text(erro)
                    )

                } else {

                    List(clientes) { cliente in

                        VStack(alignment: .leading, spacing: 6) {

                            Text(cliente.nome)
                                .font(.headline)

                            if let email = cliente.email {
                                Label(email, systemImage: "envelope")
                                    .font(.caption)
                            }

                            if let telefone = cliente.telefone {
                                Label(telefone, systemImage: "phone")
                                    .font(.caption)
                            }

                            if let documento = cliente.documento {
                                Text(documento)
                                    .font(.caption2)
                                    .foregroundStyle(.secondary)
                            }
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
            .navigationTitle("Clientes")
        }
        .task {
            await carregarClientes()
        }
    }

    private func carregarClientes() async {

        do {
            clientes = try await APIService.shared.buscarClientes()
        } catch {
            self.erro = error.localizedDescription
        }

        carregando = false
    }
}

#Preview {
    ClientesView()
}
