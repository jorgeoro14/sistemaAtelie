import { Link, useNavigate } from 'react-router-dom';
import { useApi } from '../lib/useApi.js';
import { Carregando, ErroTela, SelosOs, Vazio } from '../components/ui.jsx';
import { dataBR, hojeISO, reais } from '../lib/format.js';

// T03 — Início (dashboard do dia). Cada cartão leva à lista já filtrada.
export default function Inicio() {
  const nav = useNavigate();
  const { dados, erro, carregando, recarregar } = useApi('/dashboard');
  if (carregando && !dados) return <Carregando />;
  if (erro) return <ErroTela erro={erro} aoTentar={recarregar} />;
  const c = dados.contadores;
  const hoje = hojeISO();

  const cartoes = [
    { n: c.atrasadas, rot: 'OS atrasadas', para: '/os?atrasadas=1', cls: c.atrasadas ? 'perigo' : '' },
    { n: c.urgentes, rot: 'Urgentes abertas', para: '/os?prioridade=urgente&abertas=1', cls: c.urgentes ? 'destaque' : '' },
    { n: c.altas, rot: 'Prioridade alta abertas', para: '/os?prioridade=alta&abertas=1' },
    { n: c.entregas_hoje, rot: 'Entregas de hoje', para: `/os?de=${hoje}&ate=${hoje}&abertas=1` },
    { n: c.compromissos_hoje, rot: 'Compromissos de hoje', para: '/agenda' },
    { n: c.prontas_aguardando, rot: 'Prontas aguardando retirada', para: '/os?status=pronto' },
    { n: c.entregues_sem_pagamento, rot: 'Entregues sem pagamento', para: '/os?status=entregue&pagamento=pendente' },
    { n: c.pagamentos_pendentes, rot: 'Pagamentos pendentes', para: '/os?pagamento=pendente' },
  ];

  return (
    <>
      <div className="pagina-cab">
        <div><h1>Início</h1><span className="suave">Resumo de hoje, {dataBR(dados.data)}</span></div>
        <Link to="/os/nova" className="btn btn-destaque">+ Nova OS</Link>
      </div>

      <div className="grade">
        {cartoes.map((k) => (
          <Link key={k.rot} to={k.para} className={`resumo ${k.cls || ''}`}>
            <span className="num">{k.n}</span><span className="rot">{k.rot}</span>
          </Link>
        ))}
      </div>

      <div className="grade" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))' }}>
        <section className="cartao">
          <h2>Atrasadas</h2>
          {dados.atrasadas.length === 0 ? <Vazio>Nenhuma OS atrasada. 🎉</Vazio> : dados.atrasadas.map((o) => (
            <div key={o.id} style={{ padding: '.5rem 0', borderTop: '1px solid var(--cor-lilas)', cursor: 'pointer' }} onClick={() => nav(`/os/${o.id}`)}>
              <strong>#{o.numero}</strong> · {o.cliente?.nome} <SelosOs os={o} />
              <div className="suave">Prevista {dataBR(o.data_prevista)} · {reais(o.total_centavos)}</div>
            </div>
          ))}
        </section>
        <section className="cartao">
          <h2>Agenda de hoje</h2>
          {dados.agenda_hoje.length === 0 ? <Vazio>Nada marcado para hoje.</Vazio> : dados.agenda_hoje.map((i) => (
            <div key={`${i.origem}-${i.id}`} style={{ padding: '.5rem 0', borderTop: '1px solid var(--cor-lilas)' }}>
              <strong>{i.hora || 'Dia todo'}</strong> · {i.titulo}
              <div className="suave">{i.origem === 'entrega' ? 'Entrega prevista' : 'Compromisso'}</div>
            </div>
          ))}
        </section>
      </div>
    </>
  );
}
