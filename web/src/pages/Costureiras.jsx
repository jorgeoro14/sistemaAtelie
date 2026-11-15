import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { useApi } from '../lib/useApi.js';
import { Campo, CampoTelefone, Carregando, Confirmar, ErroTela, Modal, Vazio, useToast } from '../components/ui.jsx';
import { mascaraTelefone, soDigitos } from '../lib/format.js';

function FormCostureira({ inicial, aoSalvar, aoCancelar }) {
  const [erroGeral, setErroGeral] = useState('');
  const { register, control, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: { nome: inicial?.nome || '', telefone: mascaraTelefone(inicial?.telefone || '') },
  });
  async function enviar(d) {
    const corpo = { nome: d.nome, telefone: soDigitos(d.telefone) || null };
    try { aoSalvar(inicial ? await api.put(`/costureiras/${inicial.id}`, corpo) : await api.post('/costureiras', corpo)); }
    catch (e) { if (!aplicarErrosDeCampo(e, setError)) setErroGeral(e.message); }
  }
  return (
    <form onSubmit={handleSubmit(enviar)}>
      {erroGeral && <div className="alerta erro">{erroGeral}</div>}
      <div className="grade-form" style={{ gridTemplateColumns: '1fr' }}>
        <Campo rotulo="Nome" obrigatorio erro={errors.nome}><input {...register('nome', { required: 'obrigatório' })} autoFocus /></Campo>
        <Campo rotulo="Telefone (opcional)" erro={errors.telefone}>
          <Controller control={control} name="telefone" rules={{ validate: (v) => !v || [10, 11].includes(soDigitos(v).length) || 'informe DDD + número' }}
            render={({ field }) => <CampoTelefone {...field} />} />
        </Campo>
      </div>
      <div className="modal-acoes"><button type="button" className="btn-sec" onClick={aoCancelar}>Cancelar</button><button className="btn-primario" disabled={isSubmitting}>Salvar</button></div>
    </form>
  );
}

// T11 — Costureiras
export default function Costureiras() {
  const avisar = useToast();
  const [modal, setModal] = useState(null);
  const { dados, erro, carregando, recarregar } = useApi('/costureiras', { por_pagina: 100 });

  async function alternar(c) {
    await api.put(`/costureiras/${c.id}`, { nome: c.nome, telefone: c.telefone, ativa: !c.ativa });
    avisar(c.ativa ? 'Costureira inativada.' : 'Costureira reativada.'); setModal(null); recarregar();
  }
  return (
    <>
      <div className="pagina-cab"><h1>Costureiras</h1><button className="btn-primario" onClick={() => setModal({ tipo: 'form' })}>+ Nova costureira</button></div>
      {carregando && !dados ? <Carregando /> : erro ? <ErroTela erro={erro} aoTentar={recarregar} /> :
        dados.dados.length === 0 ? <Vazio>Nenhuma costureira cadastrada.</Vazio> : (
          <table className="tabela">
            <thead><tr><th>Nome</th><th>Telefone</th><th>Situação</th><th></th></tr></thead>
            <tbody>{dados.dados.map((c) => (
              <tr key={c.id}>
                <td data-label="Nome"><strong>{c.nome}</strong></td><td data-label="Telefone">{c.telefone ? mascaraTelefone(c.telefone) : '—'}</td>
                <td data-label="Situação">{c.ativa ? 'Ativa' : <span className="selo cancelado">Inativa</span>}</td>
                <td className="sem-rotulo"><div className="acoes">
                  <button className="btn-sec btn-pequeno" onClick={() => setModal({ tipo: 'form', item: c })}>Editar</button>
                  {c.ativa ? <button className="btn-perigo btn-pequeno" onClick={() => setModal({ tipo: 'inativar', item: c })}>Inativar</button>
                    : <button className="btn-sec btn-pequeno" onClick={() => alternar(c)}>Reativar</button>}
                </div></td>
              </tr>))}</tbody>
          </table>)}
      {modal?.tipo === 'form' && <Modal titulo={modal.item ? 'Editar costureira' : 'Nova costureira'} aoFechar={() => setModal(null)}>
        <FormCostureira inicial={modal.item} aoCancelar={() => setModal(null)} aoSalvar={() => { avisar('Costureira salva.'); setModal(null); recarregar(); }} /></Modal>}
      {modal?.tipo === 'inativar' && <Confirmar perigo titulo="Inativar costureira" mensagem={`Inativar ${modal.item.nome}? Ela some das novas OS, mas permanece nas antigas.`}
        rotuloConfirmar="Inativar" aoFechar={() => setModal(null)} aoConfirmar={() => alternar(modal.item)} />}
    </>
  );
}
