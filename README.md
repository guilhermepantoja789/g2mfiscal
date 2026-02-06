# G2M Fiscal

O **G2M Fiscal** é uma plataforma SaaS robusta para gestão fiscal e financeira de empresas. Desenvolvido com Laravel, o sistema oferece soluções completas para emissão de notas fiscais (NF-e/NFS-e), gestão de clientes, controle de serviços recorrentes e um módulo financeiro integrado.

## 🚀 Funcionalidades Principais

*   **Gestão Multi-Empresa**: Gerencie múltiplas empresas em uma única conta.
*   **Emissão de Notas Fiscais**:
    *   Emissão de NF-e e NFS-e.
    *   Impressão e download de DANFSE.
    *   Envio por e-mail.
*   **Gestão de Cadastros**:
    *   Clientes e Fornecedores.
    *   Serviços.
    *   Equipe com controle de permissões.
*   **Recorrência**: Gestão de assinaturas e pagamentos recorrentes.
*   **Certificados Digitais**: Suporte para upload e gestão de certificados A1.
*   **Módulo Financeiro (Feature Flag)**:
    *   Controle de Cobranças.
    *   Carteira Digital (Depósitos, Saques).
    *   *Nota: Requer ativação via configuração.*

## 🛠 Tech Stack

*   **Backend**: [Laravel 12](https://laravel.com)
*   **Linguagem**: PHP 8.2+
*   **Frontend**: [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev), Blade Templates.
*   **Banco de Dados**: MySQL / PostgreSQL.
*   **Build Tool**: [Vite](https://vitejs.dev).
*   **Nota Fiscal**: Integração via SPED/NFePHP.

## 📋 Pré-requisitos

Certifique-se de ter instalado em sua máquina:

*   PHP >= 8.2
*   Composer
*   Node.js & NPM
*   Banco de Dados (MySQL ou PostgreSQL)

## 🔧 Instalação

1.  **Clone o repositório**:
    ```bash
    git clone https://github.com/seu-usuario/g2m-fiscal.git
    cd g2m-fiscal
    ```

2.  **Instale as dependências do PHP**:
    ```bash
    composer install
    ```

3.  **Instale as dependências do Frontend**:
    ```bash
    npm install
    ```

4.  **Configure o ambiente**:
    Copie o arquivo de exemplo `.env` e configure suas variáveis de ambiente (banco de dados, email, etc).
    ```bash
    cp .env.example .env
    ```

5.  **Gere a chave da aplicação**:
    ```bash
    php artisan key:generate
    ```

6.  **Execute as migrações do banco de dados**:
    ```bash
    php artisan migrate
    ```

7.  **Compile os assets**:
    ```bash
    npm run build
    ```

## ⚙️ Configuração

### Módulo Financeiro
O módulo financeiro está protegido por uma *Feature Flag*. Para habilitá-lo, adicione a seguinte linha ao seu arquivo `.env`:

```ini
FEATURE_FINANCEIRO=true
```

## 🚀 Executando a Aplicação

Para iniciar o servidor de desenvolvimento:

```bash
php artisan serve
```

E em outro terminal, para assistir as mudanças nos arquivos estáticos (se necessário durante desenvolvimento):

```bash
npm run dev
```

Acesse a aplicação em `http://localhost:8000`.

## 🧪 Testes

Para executar a suíte de testes automatizados:

```bash
php artisan test
```

## 📄 Licença

Este projeto está licenciado sob a licença [MIT](https://opensource.org/licenses/MIT).
