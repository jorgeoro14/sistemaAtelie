import { useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useApi, useDebounce } from '../lib/useApi.js';
import { Carregando, CampoBusca, ErroTela, Paginacao, SelosOs, SeloPago, Vazio } from '../components/ui.jsx';
import { dataBR, reais, ROTULO_STATUS } from '../lib/format.js';

// T06 — Lista de OS (filtros guardados na URL, para os cartões do início funcionarem como link)
export default function ListaOs() {
  const [sp, setSp] = useSearchParams();
  const nav = useNavigate();
  const [q, setQ] = useState(sp.get('q') || '');
  const qDeb = useDebounce(q);
  const filtros = Object.fromEntries(sp.entries());
  const { dados: costureiras } = useApi('/costureiras', { por_pagina: 100 });

  useEffect(() => {
    const n = new URLSearchParams(sp);
    qDeb ? n.set('q', qDeb) : n.delete('q');
    n.delete('pagina');
    if (n.toString() !== sp.toString()) setSp(n, { replace: true });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [qDeb]);

  const { dados, erro, carregando, recarregar } = useApi('/os', { ...filtros, por_pagina: 20 });

  function definir(chave, valor) {
    const n = new URLSearchParams(sp);
    valor ? n.set(chave, valor) : n.delete(chave);
    if (chave !== 'pagina') n.delete('pagina');
    setSp(n);
  }

  const limpar = () => { setQ(''); setSp({}); };

  return (
    <>
      <div className="pagina-cab">
        <h1>Ordens de serviço</h1>
        <Link to="/os/nova" className="btn btn-primario">+ Nova OS</Link>
      </div>

      <div className="filtros">
        <CampoBusca valor={q} aoMudar={setQ} placeholder="Buscar por cliente, telefone ou nº" />
        <select aria-label="Status" value={filtros.status || ''} onChange={(e) => definir('status', e.target.value)}>
          <option value="">Todos os status</option>
          {Object.entries(ROTULO_STATUS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
        <select aria-label="Prioridade" value={filtros.prioridade || ''} onChange={(e) => definir('prioridade', e.target.value)}>
          <option value="">Qualquer prioridade</option>
          <option value="urgente">Urgente</option><option value="alta">Alta</option><option value="normal">Normal</option>
        </select>
        <select aria-label="Costureira" value={filtros.costureira_id || ''} onChange={(e) => definir('costureira_id', e.target.value)}>
          <option value="">Todas as costureiras</option>
          {costureiras?.dados.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
        </select>
        <select aria-label="Pagamento" value={filtros.pagamento || ''} onChange={(e) => definir('pagamento', e.target.value)}>
          <option value="">Pago ou pendente</option><option value="pendente">Pendente</option><option value="pago">Pago</option>
        </select>
        <label className="campo">Entrega de<input type="date" value={filtros.de || ''} onChange={(e) => definir('de', e.target.value)} /></label>
        <label className="campo">até<input type="date" value={filtros.ate || ''} onChange={(e) => definir('ate', e.target.value)} /></label>
        <button className="btn-sec" onClick={limpar}>Limpar filtros</button>
      </div>
      {(filtros.atrasadas || filtros.abertas) && (
        <p className="alerta info">Filtro ativo: {filtros.atrasadas ? 'somente atrasadas' : 'somente OS abertas'}. <button className="btn-link" onClick={() => { definir('atrasadas', ''); definir('abertas', ''); }}>remover</button></p>
      )}

      {carregando && !dados ? <Carregando /> : erro ? <ErroTela erro={erro} aoTentar={recarregar} /> :
        dados.dados.length === 0 ? <Vazio>Nenhuma ordem de serviço encontrada.</Vazio> : (
          <table className="tabela">
            <thead><tr><th>Nº</th><th>Cliente</th><th>Serviços</th><th>Entrega prevista</th><th className="num">Total</th><th>Status</th><th>Pago?</th></tr></thead>
            <tbody>
              {dados.dados.map((o) => (
                <tr key={o.id} className="clicavel" onClick={() => nav(`/os/${o.id}`)}>
                  <td data-label="Nº"><strong>#{o.numero}</strong></td>
                  <td data-label="Cliente">{o.cliente?.nome}</td>
                  <td data-label="Serviços">{o.resumo_servicos}</td>
                  <td data-label="Entrega prevista">{dataBR(o.data_prevista)}</td>
                  <td data-label="Total" className="num">{reais(o.total_centavos)}</td>
                  <td data-label="Status"><SelosOs os={o} /></td>
                  <td data-label="Pago?">{o.status === 'cancelado' ? '—' : <SeloPago situacao={o.situacao_pagamento} />}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      <Paginacao paginacao={dados?.paginacao} aoMudar={(p) => definir('pagina', String(p))} />
    </>
  );
}
