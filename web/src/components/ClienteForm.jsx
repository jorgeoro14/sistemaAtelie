import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { mascaraTelefone, soDigitos } from '../lib/format.js';
import { Campo, CampoTelefone, useToast } from './ui.jsx';

// Formulário de cliente reutilizável: usado na tela de clientes e no "cadastrar novo" da OS.
export default function ClienteForm({ inicial, aoSalvar, aoCancelar }) {
  const avisar = useToast();
  const [erroGeral, setErroGeral] = useState('');
  const { register, control, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: {
      nome: inicial?.nome || '',
      telefone: inicial ? mascaraTelefone(inicial.telefone) : '',
      observacoes: inicial?.observacoes || '',
    },
  });

  async function enviar(d) {
    setErroGeral('');
    const corpo = { nome: d.nome, telefone: soDigitos(d.telefone), observacoes: d.observacoes || null };
    try {
      const r = inicial ? await api.put(`/clientes/${inicial.id}`, corpo) : await api.post('/clientes', corpo);
      r.avisos?.forEach((a) => avisar(a.mensagem, 'aviso')); // RN14: aviso de telefone duplicado
      avisar('Cliente salvo.');
      aoSalvar(r);
    } catch (e) {
      if (!aplicarErrosDeCampo(e, setError)) setErroGeral(e.message);
    }
  }

  // stopPropagation: quando aberto dentro do formulário da OS, não envia a OS junto
  return (
    <form onSubmit={(e) => { e.stopPropagation(); handleSubmit(enviar)(e); }}>
      {erroGeral && <div className="alerta erro" role="alert">{erroGeral}</div>}
      <div className="grade-form" style={{ gridTemplateColumns: '1fr' }}>
        <Campo rotulo="Nome" obrigatorio erro={errors.nome}>
          <input {...register('nome', { required: 'obrigatório' })} autoFocus />
        </Campo>
        <Campo rotulo="Telefone (com DDD)" obrigatorio erro={errors.telefone}>
          <Controller name="telefone" control={control}
            rules={{ validate: (v) => [10, 11].includes(soDigitos(v).length) || 'informe DDD + número' }}
            render={({ field }) => <CampoTelefone {...field} />} />
        </Campo>
        <Campo rotulo="Observações" erro={errors.observacoes}>
          <textarea rows={3} {...register('observacoes')} />
        </Campo>
      </div>
      <div className="modal-acoes">
        <button type="button" className="btn-sec" onClick={aoCancelar}>Cancelar</button>
        <button className="btn-primario" disabled={isSubmitting}>Salvar</button>
      </div>
    </form>
  );
}
