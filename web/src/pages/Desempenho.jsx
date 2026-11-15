import { useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { useApi } from '../lib/useApi.js';
import { useAuth } from '../lib/auth.jsx';
import { Carregando, ErroTela, Vazio } from '../components/ui.jsx';
import { hojeISO, paraISO, reais, ROTULO_FORMA } from '../lib/format.js';
import Erro from './Erro.jsx';

const MESES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const CORES_FORMA = { dinheiro: '#1A8F4E', pix: '#9700CA', cartao_debito: '#B26A00', cartao_credito: '#C2006F', outro: '#6B5A75' };

function periodoDe(tipo, personalizado) {
  const [a, m] = hojeISO().split('-').map(Number);
  if (tipo === 'mes') return { de: paraISO(new Date(a, m - 1, 1)), ate: paraISO(new Date(a, m, 0)) };
  if (tipo === 'anterior') return { de: paraISO(new Date(a, m - 2, 1)), ate: paraISO(new Date(a, m - 1, 0)) };
  if (tipo === '3meses') return { de: paraISO(new Date(a, m - 3, 1)), ate: paraISO(new Date(a, m, 0)) };
  return personalizado;
}

// T14 — Dashboard de desempenho (admin)
export default function Desempenho() {
  const { usuario } = useAuth();
  const [tipo, setTipo] = useState('mes');
  const [pers, setPers] = useState({ de: hojeISO(), ate: hojeISO() });
  const [ordem, setOrdem] = useState({ campo: 'valor_total_centavos', dir: -1 });
  const periodo = periodoDe(tipo, pers);
  const valido = periodo.de && periodo.ate && periodo.ate >= periodo.de;
  const ano = Number(periodo.ate?.slice(0, 4)) || new Date().getFullYear();

  const resumo = useApi('/relatorios/resumo', periodo, valido);
  const costureiras = useApi('/relatorios/costureiras', periodo, valido);
  const clientes = useApi('/relatorios/clientes', periodo, valido);
  const mensal = useApi('/relatorios/faturamento-mensal', { ano });

  const linhas = useMemo(() => {
    const l = [...(costureiras.dados?.dados || [])];
    l.sort((x, y) => ((x[ordem.campo] ?? -1) - (y[ordem.campo] ?? -1)) * ordem.dir || x.nome.localeCompare(y.nome));
    return l;
  }, [costureiras.dados, ordem]);

  if (usuario?.perfil !== 'admin') return <Erro codigo={403} />;
  const ordenar = (campo) => setOrdem((o) => ({ campo, dir: o.campo === campo ? -o.dir : -1 }));
  const th = (campo, rotulo, num) => <th className={num ? 'num' : ''}><button onClick={() => ordenar(campo)}>{rotulo}{ordem.campo === campo ? (ordem.dir === 1 ? ' ▲' : ' ▼') : ''}</button></th>;

  const r = resumo.dados;
  const dadosMes = (mensal.dados?.meses || []).map((m) => ({ mes: MESES[m.mes - 1], reais: m.total_centavos / 100 }));
  const totaisForma = Object.keys(ROTULO_FORMA).map((f) => ({
    forma: ROTULO_FORMA[f], chave: f, reais: (mensal.dados?.meses || []).reduce((s, m) => s + m.por_forma[f], 0) / 100,
  }));
  const fmt = (v) => reais(Math.round(v * 100));

  return (
    <>
      <div className="pagina-cab"><h1>Desempenho</h1></div>
      <div className="filtros">
        <select aria-label="Período" value={tipo} onChange={(e) => setTipo(e.target.value)}>
          <option value="mes">Mês atual</option><option value="anterior">Mês anterior</option><option value="3meses">Últimos 3 meses</option><option value="custom">Personalizado</option>
        </select>
        {tipo === 'custom' && <>
          <label className="campo">De<input type="date" value={pers.de} onChange={(e) => setPers({ ...pers, de: e.target.value })} /></label>
          <label className="campo">Até<input type="date" value={pers.ate} onChange={(e) => setPers({ ...pers, ate: e.target.value })} /></label>
        </>}
      </div>
      {!valido && <div className="alerta erro">A data final não pode ser anterior à inicial.</div>}
      {resumo.erro && <ErroTela erro={resumo.erro} aoTentar={resumo.recarregar} />}
      {resumo.carregando && !r ? <Carregando /> : r && (
        <div className="grade">
          <div className="resumo"><span className="num">{reais(r.faturamento_centavos)}</span><span className="rot">Faturamento (OS pagas no período)</span></div>
          <div className="resumo"><span className="num">{reais(r.a_receber_centavos)}</span><span className="rot">A receber (OS não pagas)</span></div>
          <div className="resumo"><span className="num">{r.os_recebidas}</span><span className="rot">OS recebidas · {r.os_entregues} entregues · {r.os_canceladas} canceladas</span></div>
          <div className="resumo"><span className="num">{reais(r.ticket_medio_centavos)}</span><span className="rot">Ticket médio ({r.os_pagas} OS pagas)</span></div>
          {clientes.dados && <div className="resumo"><span className="num">{clientes.dados.atendidos_periodo}</span><span className="rot">Clientes atendidos · {clientes.dados.novos_periodo} novos · {clientes.dados.total_cadastrados} cadastrados</span></div>}
        </div>
      )}

      <div className="grade" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))' }}>
        <section className="cartao">
          <h2>Faturamento por mês — {ano}</h2>
          {dadosMes.every((d) => d.reais === 0) ? <Vazio>Sem pagamentos em {ano}.</Vazio> : (
            <ResponsiveContainer width="100%" height={260}><BarChart data={dadosMes}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="mes" /><YAxis tickFormatter={(v) => `R$ ${v}`} width={70} /><Tooltip formatter={(v) => fmt(v)} /><Bar dataKey="reais" name="Faturamento" fill="#9700CA" /></BarChart></ResponsiveContainer>
          )}
        </section>
        <section className="cartao">
          <h2>Por forma de pagamento — {ano}</h2>
          {totaisForma.every((d) => d.reais === 0) ? <Vazio>Sem pagamentos em {ano}.</Vazio> : (
            <ResponsiveContainer width="100%" height={260}><BarChart data={totaisForma}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="forma" interval={0} tick={{ fontSize: 11 }} /><YAxis tickFormatter={(v) => `R$ ${v}`} width={70} /><Tooltip formatter={(v) => fmt(v)} /><Bar dataKey="reais" name="Total" fill="#9700CA" /></BarChart></ResponsiveContainer>
          )}
        </section>
      </div>

      <section className="cartao">
        <h2>Desempenho por costureira</h2>
        {costureiras.carregando && !costureiras.dados ? <Carregando /> : linhas.length === 0 ? <Vazio>Nenhuma OS recebida neste período.</Vazio> : (<>
          <table className="tabela">
            <thead><tr>{th('nome', 'Costureira')}{th('os', 'OS', true)}{th('concluidas', 'Concluídas', true)}{th('valor_total_centavos', 'Valor total', true)}{th('tempo_medio_dias', 'Tempo médio até pronto', true)}</tr></thead>
            <tbody>{linhas.map((l) => (
              <tr key={l.costureira_id ?? 'sem'}><td data-label="Costureira"><strong>{l.nome}</strong></td><td data-label="OS" className="num">{l.os}</td><td data-label="Concluídas" className="num">{l.concluidas}</td>
                <td data-label="Valor total" className="num">{reais(l.valor_total_centavos)}</td><td data-label="Tempo médio" className="num">{l.tempo_medio_dias === null ? '—' : `${String(l.tempo_medio_dias).replace('.', ',')} dias`}</td></tr>))}</tbody>
          </table>
          <ResponsiveContainer width="100%" height={260}><BarChart data={linhas.map((l) => ({ nome: l.nome, OS: l.os, Concluídas: l.concluidas }))}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="nome" /><YAxis allowDecimals={false} /><Tooltip /><Legend /><Bar dataKey="OS" fill="#9700CA" /><Bar dataKey="Concluídas" fill="#1A8F4E" /></BarChart></ResponsiveContainer>
        </>)}
      </section>

      <section className="cartao">
        <h2>Clientes que mais gastaram</h2>
        {clientes.dados?.melhores.length ? (
          <table className="tabela"><thead><tr><th>#</th><th>Cliente</th><th className="num">OS pagas</th><th className="num">Total</th></tr></thead>
            <tbody>{clientes.dados.melhores.map((c, i) => <tr key={c.id}><td data-label="#">{i + 1}</td><td data-label="Cliente">{c.nome}</td><td data-label="OS pagas" className="num">{c.os}</td><td data-label="Total" className="num">{reais(c.total_centavos)}</td></tr>)}</tbody></table>
        ) : <Vazio>Sem pagamentos neste período.</Vazio>}
      </section>
    </>
  );
}
