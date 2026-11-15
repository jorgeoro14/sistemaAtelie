import { useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import ptBr from '@fullcalendar/core/locales/pt-br';
import { useForm } from 'react-hook-form';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { useApi } from '../lib/useApi.js';
import ClienteSelect from '../components/ClienteSelect.jsx';
import { Campo, Confirmar, Modal, useToast } from '../components/ui.jsx';
import { hojeISO, ROTULO_TIPO_AGENDA } from '../lib/format.js';

// T05 — Compromisso (modal): cliente, data e hora, tipo, OS opcional, observação, status
function CompromissoModal({ inicial, aoFechar, aoSalvar }) {
  const avisar = useToast();
  const [cliente, setCliente] = useState(null);
  const [erroGeral, setErroGeral] = useState('');
  const [cancelar, setCancelar] = useState(false);
  const { register, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: { data: inicial?.data || hojeISO(), hora: inicial?.hora || '09:00', tipo: inicial?.tipo || 'prova', os_id: inicial?.os_id ? String(inicial.os_id) : '', observacao: inicial?.observacao || '', status: inicial?.status || 'marcado' },
  });
  const { dados: existente } = useApi(`/agendamentos/${inicial?.id}`, null, !!inicial?.id);
  const { dados: ordens } = useApi('/os', { cliente_id: cliente?.id, abertas: 1, por_pagina: 50 }, !!cliente);

  useEffect(() => {
    if (existente) setCliente({ id: existente.cliente_id, nome: existente.cliente_nome, telefone: '' });
  }, [existente]);

  async function enviar(d) {
    setErroGeral('');
    if (!cliente) { setError('cliente_id', { message: 'escolha o cliente' }); return; }
    const corpo = { cliente_id: cliente.id, os_id: d.os_id ? Number(d.os_id) : null, data_hora: `${d.data} ${d.hora}`, tipo: d.tipo, observacao: d.observacao || null, status: d.status };
    try {
      const r = inicial?.id ? await api.put(`/agendamentos/${inicial.id}`, corpo) : await api.post('/agendamentos', corpo);
      r.avisos?.forEach((a) => avisar(a.mensagem, 'aviso')); // RN16: choque de horário só avisa
      avisar('Compromisso salvo.');
      aoSalvar();
    } catch (e) {
      if (!aplicarErrosDeCampo(e, setError, (k) => (k === 'data_hora' ? 'data' : k))) setErroGeral(e.message);
    }
  }
  async function cancelarCompromisso() {
    await api.del(`/agendamentos/${inicial.id}`); avisar('Compromisso cancelado.'); aoSalvar();
  }

  return (
    <Modal titulo={inicial?.id ? 'Editar compromisso' : 'Novo compromisso'} aoFechar={aoFechar}>
      <form onSubmit={handleSubmit(enviar)}>
        {erroGeral && <div className="alerta erro" role="alert">{erroGeral}</div>}
        <div className="grade-form" style={{ gridTemplateColumns: '1fr 1fr' }}>
          <div style={{ gridColumn: '1 / -1' }}><Campo rotulo="Cliente" obrigatorio><ClienteSelect valor={cliente} aoMudar={setCliente} erro={errors.cliente_id} /></Campo></div>
          <Campo rotulo="Data" obrigatorio erro={errors.data}><input type="date" {...register('data', { required: 'obrigatória' })} /></Campo>
          <Campo rotulo="Hora" obrigatorio erro={errors.hora}><input type="time" {...register('hora', { required: 'obrigatória' })} /></Campo>
          <Campo rotulo="Tipo"><select {...register('tipo')}>{Object.entries(ROTULO_TIPO_AGENDA).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Campo>
          <Campo rotulo="Status"><select {...register('status')}><option value="marcado">Marcado</option><option value="realizado">Realizado</option><option value="cancelado">Cancelado</option></select></Campo>
          <div style={{ gridColumn: '1 / -1' }}>
            <Campo rotulo="OS vinculada (opcional)" erro={errors.os_id}>
              <select {...register('os_id')} disabled={!cliente}>
                <option value="">Nenhuma</option>
                {ordens?.dados.map((o) => <option key={o.id} value={o.id}>#{o.numero} — {o.resumo_servicos}</option>)}
                {inicial?.os_id && !ordens?.dados.some((o) => o.id === inicial.os_id) && <option value={inicial.os_id}>OS #{inicial.os_numero}</option>}
              </select>
            </Campo>
          </div>
          <div style={{ gridColumn: '1 / -1' }}><Campo rotulo="Observação" erro={errors.observacao}><textarea rows={2} {...register('observacao')} /></Campo></div>
        </div>
        <div className="modal-acoes">
          {inicial?.id && <button type="button" className="btn-perigo" onClick={() => setCancelar(true)}>Cancelar compromisso</button>}
          <button type="button" className="btn-sec" onClick={aoFechar}>Fechar</button>
          <button className="btn-primario" disabled={isSubmitting}>Salvar</button>
        </div>
      </form>
      {cancelar && <Confirmar perigo titulo="Cancelar compromisso" mensagem="O compromisso será marcado como cancelado." rotuloConfirmar="Cancelar compromisso" aoFechar={() => setCancelar(false)} aoConfirmar={cancelarCompromisso} />}
    </Modal>
  );
}

// T04 — Agenda (mês / semana / dia): entregas previstas + compromissos
export default function Agenda() {
  const nav = useNavigate();
  const [sp, setSp] = useSearchParams();
  const calRef = useRef(null);
  const [costureira, setCostureira] = useState('');
  const [modal, setModal] = useState(null);
  const [erro, setErro] = useState('');
  const { dados: costureiras } = useApi('/costureiras', { por_pagina: 100 });

  const recarregar = () => calRef.current?.getApi().refetchEvents();
  useEffect(() => { recarregar(); }, [costureira]);

  // link vindo da API: /agenda?compromisso=ID abre o modal
  useEffect(() => {
    const idc = sp.get('compromisso');
    if (idc) { api.get(`/agendamentos/${idc}`).then((a) => setModal({ id: a.id, data: a.data_hora.slice(0, 10), hora: a.data_hora.slice(11, 16), tipo: a.tipo, os_id: a.os_id, os_numero: a.os_numero, observacao: a.observacao, status: a.status })); setSp({}, { replace: true }); }
  }, [sp, setSp]);

  async function buscar(info, ok, falha) {
    try {
      const fim = new Date(info.end.getTime() - 1);
      const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
      const r = await api.get('/agenda', { de: iso(info.start), ate: iso(fim), costureira_id: costureira });
      setErro('');
      ok(r.dados.map((i) => ({
        id: `${i.origem}-${i.id}`,
        title: i.origem === 'entrega' ? `📦 ${i.cliente_nome} · OS #${i.os_numero}${i.atrasada ? ' (atrasada)' : ''}` : `${ROTULO_TIPO_AGENDA[i.tipo]} · ${i.cliente_nome}`,
        start: i.hora ? `${i.data}T${i.hora}` : i.data,
        allDay: !i.hora,
        backgroundColor: i.origem === 'entrega' ? (i.atrasada ? '#D92D20' : '#9700CA') : '#C2006F',
        borderColor: 'transparent',
        extendedProps: i,
      })));
    } catch (e) { setErro(e.message); falha(e); }
  }

  return (
    <>
      <div className="pagina-cab">
        <h1>Agenda</h1>
        <div className="acoes">
          <select aria-label="Filtrar por costureira" value={costureira} onChange={(e) => setCostureira(e.target.value)}>
            <option value="">Todas as costureiras</option>
            {costureiras?.dados.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
          </select>
          <button className="btn-primario" onClick={() => setModal({})}>+ Novo compromisso</button>
        </div>
      </div>
      <p className="suave"><span className="selo" style={{ background: '#9700CA', color: '#fff' }}>Entrega prevista</span>{' '}
        <span className="selo" style={{ background: '#C2006F', color: '#fff' }}>Compromisso</span>{' '}
        <span className="selo atrasada">Entrega atrasada</span></p>
      {erro && <div className="alerta erro" role="alert">{erro}</div>}
      <div className="cartao">
        <FullCalendar
          ref={calRef}
          plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
          locales={[ptBr]} locale="pt-br" initialView="dayGridMonth" height="auto"
          headerToolbar={{ left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' }}
          events={buscar} dayMaxEvents={4}
          dateClick={(i) => setModal({ data: i.dateStr.slice(0, 10), hora: i.dateStr.length > 10 ? i.dateStr.slice(11, 16) : '09:00' })}
          eventClick={(i) => {
            const p = i.event.extendedProps;
            if (p.origem === 'entrega') nav(`/os/${p.id}`); // abre a OS (T08)
            else setModal({ id: p.id, data: p.data, hora: p.hora, tipo: p.tipo, os_id: p.os_id, os_numero: p.os_numero, status: p.status });
          }}
        />
      </div>
      {modal && <CompromissoModal inicial={modal} aoFechar={() => setModal(null)} aoSalvar={() => { setModal(null); recarregar(); }} />}
    </>
  );
}
