import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../lib/api.js';
import { useApi } from '../lib/useApi.js';
import { Campo, Carregando, Confirmar, ErroTela, Modal, SelosOs, SeloPago, useToast } from '../components/ui.jsx';
import { dataBR, hojeISO, linkWhatsApp, mascaraTelefone, reais, ROTULO_FORMA, ROTULO_STATUS } from '../lib/format.js';

const VOLTAR = { em_andamento: 'recebido', pronto: 'em_andamento', entregue: 'pronto' };

// T08 — Detalhe da OS (acompanhamento). Os botões mostram só o que faz sentido para o status atual.
export default function DetalheOs() {
  const { id } = useParams();
  const avisar = useToast();
  const { dados: os, erro, carregando, recarregar } = useApi(`/os/${id}`);
  const [modal, setModal] = useState(null); // {tipo, ...}
  const [data, setData] = useState(hojeISO());
  const [forma, setForma] = useState('dinheiro');
  const [erroAcao, setErroAcao] = useState('');

  if (carregando && !os) return <Carregando />;
  if (erro) return <ErroTela erro={erro} aoTentar={recarregar} />;

  async function executar(fn, mensagem) {
    setErroAcao('');
    try { await fn(); avisar(mensagem); setModal(null); await recarregar(); }
    catch (e) { setModal(null); setErroAcao(e.message); } // ex.: "OS paga não pode ser editada"
  }
  const mudarStatus = (status, d) => executar(() => api.patch(`/os/${id}/status`, d ? { status, data: d } : { status }), `Status: ${ROTULO_STATUS[status]}.`);

  const aberta = !['entregue', 'cancelado'].includes(os.status);
  const podeEditar = !os.paga && os.status !== 'cancelado';
  const nomeArquivo = `OS-${os.numero}`;

  return (
    <>
      <div className="pagina-cab">
        <div>
          <h1>OS #{os.numero}</h1>
          <SelosOs os={os} />{' '}{os.status !== 'cancelado' && <SeloPago situacao={os.situacao_pagamento} />}
        </div>
        <div className="acoes nao-imprimir">
          {os.status === 'recebido' && <button className="btn-sec" onClick={() => mudarStatus('em_andamento')}>Iniciar</button>}
          {['recebido', 'em_andamento'].includes(os.status) && <button className="btn-primario" onClick={() => { setData(hojeISO()); setModal({ tipo: 'pronto' }); }}>Marcar como pronto</button>}
          {os.status === 'pronto' && <button className="btn-primario" onClick={() => { setData(hojeISO()); setModal({ tipo: 'entregue' }); }}>Marcar como entregue</button>}
          {!os.paga && os.status !== 'cancelado' && <button className="btn-sec" onClick={() => setModal({ tipo: 'pagar' })}>Registrar pagamento</button>}
          {os.paga && <button className="btn-perigo" onClick={() => setModal({ tipo: 'estornar' })}>Estornar pagamento</button>}
          {podeEditar && <Link className="btn btn-sec" to={`/os/${id}/editar`}>Editar</Link>}
          {os.cliente && <a className="btn btn-sec" href={linkWhatsApp(os.cliente.telefone)} target="_blank" rel="noreferrer">WhatsApp</a>}
          <button className="btn-sec" onClick={() => { const t = document.title; document.title = nomeArquivo; window.print(); document.title = t; }}>Exportar PDF</button>
          {aberta && <button className="btn-perigo" onClick={() => setModal({ tipo: 'cancelar' })}>Cancelar OS</button>}
          {os.status === 'cancelado' && <button className="btn-sec" onClick={() => mudarStatus('recebido')}>Reabrir</button>}
          {VOLTAR[os.status] && <button className="btn-link" onClick={() => setModal({ tipo: 'voltar' })}>Voltar para “{ROTULO_STATUS[VOLTAR[os.status]]}”</button>}
        </div>
      </div>

      {erroAcao && <div className="alerta erro" role="alert">{erroAcao}</div>}

      <section className="cartao">
        <dl className="dl">
          <div><dt>Cliente</dt><dd><Link to={`/clientes/${os.cliente_id}`}>{os.cliente?.nome}</Link> · {mascaraTelefone(os.cliente?.telefone)}</dd></div>
          <div><dt>Costureira</dt><dd>{os.costureira?.nome || 'Sem costureira'}</dd></div>
          <div><dt>Recebimento</dt><dd>{dataBR(os.data_recebimento)}</dd></div>
          <div><dt>Entrega prevista</dt><dd>{dataBR(os.data_prevista)}</dd></div>
          <div><dt>Ficou pronto em</dt><dd>{dataBR(os.data_pronto)}</dd></div>
          <div><dt>Entregue em</dt><dd>{dataBR(os.data_entrega)}</dd></div>
          <div><dt>Pagamento</dt><dd>{os.paga ? `${ROTULO_FORMA[os.forma_pagamento]} em ${dataBR(os.data_pagamento)}` : 'Pendente'}</dd></div>
          <div><dt>Criada por</dt><dd>{os.criado_por_nome} em {dataBR(os.criado_em)}</dd></div>
        </dl>
        {os.observacoes && <p><strong>Observações:</strong> {os.observacoes}</p>}
      </section>

      <section className="cartao">
        <h2>Serviços</h2>
        <table className="tabela">
          <thead><tr><th>Tipo</th><th>O que será feito</th><th className="num">Valor</th></tr></thead>
          <tbody>
            {os.itens.map((i) => (
              <tr key={i.id}><td data-label="Tipo">{i.servico_nome}</td><td data-label="Descrição">{i.descricao}</td><td data-label="Valor" className="num">{reais(i.valor_centavos)}</td></tr>
            ))}
          </tbody>
        </table>
        <div className="total-os">Total: {reais(os.total_centavos)}</div>
      </section>

      {modal?.tipo === 'pronto' && (
        <Modal titulo="Marcar como pronto" aoFechar={() => setModal(null)}>
          <Campo rotulo="Data em que ficou pronto"><input type="date" value={data} onChange={(e) => setData(e.target.value)} /></Campo>
          <div className="modal-acoes"><button className="btn-sec" onClick={() => setModal(null)}>Voltar</button><button className="btn-primario" onClick={() => mudarStatus('pronto', data)}>Confirmar</button></div>
        </Modal>
      )}
      {modal?.tipo === 'entregue' && (
        <Modal titulo="Marcar como entregue" aoFechar={() => setModal(null)}>
          <Campo rotulo="Data da entrega"><input type="date" value={data} onChange={(e) => setData(e.target.value)} /></Campo>
          <div className="modal-acoes"><button className="btn-sec" onClick={() => setModal(null)}>Voltar</button><button className="btn-primario" onClick={() => mudarStatus('entregue', data)}>Confirmar</button></div>
        </Modal>
      )}
      {modal?.tipo === 'pagar' && (
        <Modal titulo={`Registrar pagamento — ${reais(os.total_centavos)}`} aoFechar={() => setModal(null)}>
          <p className="suave">O pagamento é único e quita a OS inteira. Registre depois que o cliente pagou na loja.</p>
          <div className="grade-form" style={{ gridTemplateColumns: '1fr' }}>
            <Campo rotulo="Forma de pagamento" obrigatorio>
              <select value={forma} onChange={(e) => setForma(e.target.value)}>
                {Object.entries(ROTULO_FORMA).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
              </select>
            </Campo>
            <Campo rotulo="Data do pagamento" obrigatorio><input type="date" max={hojeISO()} value={data} onChange={(e) => setData(e.target.value)} /></Campo>
          </div>
          <div className="modal-acoes"><button className="btn-sec" onClick={() => setModal(null)}>Voltar</button>
            <button className="btn-primario" onClick={() => executar(() => api.post(`/os/${id}/pagamento`, { forma, data_pagamento: data }), 'Pagamento registrado.')}>Registrar</button></div>
        </Modal>
      )}
      {modal?.tipo === 'estornar' && <Confirmar titulo="Estornar pagamento" mensagem="O pagamento será desfeito e a OS volta a ficar pendente. Continuar?" perigo rotuloConfirmar="Estornar"
        aoFechar={() => setModal(null)} aoConfirmar={() => executar(() => api.del(`/os/${id}/pagamento`), 'Pagamento estornado.')} />}
      {modal?.tipo === 'cancelar' && <Confirmar titulo="Cancelar OS" mensagem={`Cancelar a OS #${os.numero}? Ela deixa de contar como pendente de pagamento.`} perigo rotuloConfirmar="Cancelar OS"
        aoFechar={() => setModal(null)} aoConfirmar={() => mudarStatus('cancelado')} />}
      {modal?.tipo === 'voltar' && <Confirmar titulo="Voltar status" mensagem={`Voltar a OS para “${ROTULO_STATUS[VOLTAR[os.status]]}”? A data correspondente será apagada.`} rotuloConfirmar="Voltar status"
        aoFechar={() => setModal(null)} aoConfirmar={() => mudarStatus(VOLTAR[os.status])} />}
    </>
  );
}
