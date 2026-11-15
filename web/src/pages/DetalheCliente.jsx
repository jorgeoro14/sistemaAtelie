import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api } from '../lib/api.js';
import { useApi } from '../lib/useApi.js';
import ClienteForm from '../components/ClienteForm.jsx';
import { Carregando, Confirmar, ErroTela, Modal, SelosOs, useToast } from '../components/ui.jsx';
import { dataBR, linkWhatsApp, mascaraTelefone, reais } from '../lib/format.js';

// T09 (detalhe) — histórico de OS e total já gasto
export default function DetalheCliente() {
  const { id } = useParams();
  const nav = useNavigate();
  const avisar = useToast();
  const { dados: c, erro, carregando, recarregar } = useApi(`/clientes/${id}`);
  const [modal, setModal] = useState(null);
  const [erroAcao, setErroAcao] = useState('');

  if (carregando && !c) return <Carregando />;
  if (erro) return <ErroTela erro={erro} aoTentar={recarregar} />;

  async function remover() {
    try {
      const r = await api.del(`/clientes/${id}`);
      avisar(r.acao === 'excluido' ? 'Cliente excluído.' : 'Cliente tem histórico: foi inativado, não excluído.');
      r.acao === 'excluido' ? nav('/clientes') : (setModal(null), recarregar());
    } catch (e) { setModal(null); setErroAcao(e.message); }
  }

  return (
    <>
      <div className="pagina-cab">
        <div><h1>{c.nome}</h1>{!c.ativo && <span className="selo cancelado">Inativo</span>}</div>
        <div className="acoes">
          <Link to={`/os/nova`} className="btn btn-primario">+ Nova OS</Link>
          <a className="btn btn-sec" href={linkWhatsApp(c.telefone)} target="_blank" rel="noreferrer">WhatsApp</a>
          <button className="btn-sec" onClick={() => setModal('editar')}>Editar</button>
          <button className="btn-perigo" onClick={() => setModal('remover')}>{c.total_os === 0 ? 'Excluir' : 'Inativar'}</button>
        </div>
      </div>
      {erroAcao && <div className="alerta erro" role="alert">{erroAcao}</div>}
      <section className="cartao">
        <dl className="dl">
          <div><dt>Telefone</dt><dd>{mascaraTelefone(c.telefone)}</dd></div>
          <div><dt>Ordens de serviço</dt><dd>{c.total_os}</dd></div>
          <div><dt>Total já gasto (OS pagas)</dt><dd>{reais(c.total_gasto_centavos)}</dd></div>
        </dl>
        {c.observacoes && <p><strong>Observações:</strong> {c.observacoes}</p>}
      </section>
      <h2>Histórico de OS</h2>
      {c.ordens.length === 0 ? <div className="vazio">Este cliente ainda não tem ordens de serviço.</div> : (
        <table className="tabela">
          <thead><tr><th>Nº</th><th>Serviços</th><th>Entrega prevista</th><th className="num">Total</th><th>Status</th></tr></thead>
          <tbody>
            {c.ordens.map((o) => (
              <tr key={o.id} className="clicavel" onClick={() => nav(`/os/${o.id}`)}>
                <td data-label="Nº">#{o.numero}</td><td data-label="Serviços">{o.resumo_servicos}</td>
                <td data-label="Entrega">{dataBR(o.data_prevista)}</td><td data-label="Total" className="num">{reais(o.total_centavos)}</td>
                <td data-label="Status"><SelosOs os={o} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
      {modal === 'editar' && <Modal titulo="Editar cliente" aoFechar={() => setModal(null)}>
        <ClienteForm inicial={c} aoCancelar={() => setModal(null)} aoSalvar={() => { setModal(null); recarregar(); }} /></Modal>}
      {modal === 'remover' && <Confirmar perigo titulo={c.total_os === 0 ? 'Excluir cliente' : 'Inativar cliente'}
        mensagem={c.total_os === 0 ? 'Este cliente não tem OS e será excluído de vez.' : 'Clientes com histórico não são apagados: ele será inativado e some das novas OS.'}
        rotuloConfirmar={c.total_os === 0 ? 'Excluir' : 'Inativar'} aoFechar={() => setModal(null)} aoConfirmar={remover} />}
    </>
  );
}
