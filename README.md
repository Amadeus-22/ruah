# Ruah

*Ruah* (רוּחַ) é a palavra hebraica para sopro, fôlego: o fôlego de vida de Gênesis 2:7.
O nome vem do trabalho do consultório, que é fisioterapia respiratória (RTA).

Agendamento para um consultório de fisioterapia com uma profissional.

O paciente (ou o responsável) abre um link, vê os atendimentos e os horários
livres e envia um pedido. A profissional confirma no painel. O sistema controla
pacotes de sessões e registra o valor combinado em cada atendimento.

## O que tem

- **Página pública** (`/`): serviços, horários livres e pedido de horário.
- **Quadro** (`/painel`): triagem em colunas, do primeiro contato ao atendimento
  realizado: quer agendar, aguardando paciente, agendado, realizado, faltou.
- **Agenda do dia** (`/painel/agenda`): os próximos 8 dias, com botão de
  WhatsApp com a mensagem pronta.
- **Financeiro** (`/painel/financeiro`): recebido, a receber e previsto no mês,
  forma de pagamento, número da nota fiscal e planilha para o contador.
- **Novo horário**: para lançar o que foi combinado por mensagem.
- **Pacientes**: cadastro (responsável e paciente), pacotes e histórico.
- **Serviços e pacotes**, **horários de atendimento**, **bloqueios** e as regras
  da agenda (intervalo entre pacientes, antecedência mínima, falta desconta sessão).

Não guarda informação clínica: nem diagnóstico, nem evolução, nem prontuário.

## Rodar na máquina

Precisa de PHP 8.4, Composer e Docker (para o MySQL).

    docker compose up -d                      # MySQL na porta 3307
    composer install
    cp .env.example .env && php artisan key:generate
    php artisan migrate --seed                # cria as tabelas e os dados de exemplo
    php artisan serve --port=8010

- Página pública: http://localhost:8010
- Painel: http://localhost:8010/painel — `fisio@local.test` / `trocar-esta-senha`

Os serviços, valores e horários do seed são exemplos, para validar o fluxo.

## Testes

    docker compose exec db mysql -uroot -proot-local \
      -e "CREATE DATABASE IF NOT EXISTS agenda_fisio_test; GRANT ALL ON agenda_fisio_test.* TO 'agenda'@'%';"
    php artisan test

## Como está organizado

| Onde | O quê |
|---|---|
| `app/Scheduling/SlotFinder.php` | A regra dos horários livres. Não lê banco nem relógio; é testada sozinha. |
| `app/Scheduling/Schedule.php` | Junta atendimentos, bloqueios e o calendário e consulta o `SlotFinder`. |
| `app/Scheduling/Booking.php` | O ciclo do agendamento: pedido, confirmação, realizado, falta, cancelamento; desconta as sessões do pacote. |
| `app/Calendar/` | `IcsFeed`: agenda que o celular assina, só de leitura (link criado em Configurações). `CalendarGateway` é a porta para uma ligação de duas vias; hoje é `NullCalendar`. |

## O que falta

- **Ler o calendário do iPhone** (iCloud, por CalDAV): implementar `CalendarGateway`.
  O sistema já aparece no celular por assinatura, mas o caminho de volta não existe;
  compromissos pessoais entram como bloqueio.
- Trocar os dados de exemplo pelos reais e definir a senha do painel.
- Aviso para a profissional quando chega um pedido. O lembrete ao paciente é enviado por ela,
  com um toque, na Agenda do dia; envio sem ela precisa da API oficial do WhatsApp.
- Revisar o texto de `/privacidade` com ela antes de publicar.
- Emissão da nota fiscal pelo sistema (hoje ele só guarda o número da nota emitida na prefeitura).
- Criar o contato no quadro sozinho quando alguém chama no WhatsApp (precisa da API oficial).
- Termo de privacidade (LGPD), hospedagem e domínio.
