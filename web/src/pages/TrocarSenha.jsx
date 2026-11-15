import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import { useState } from 'react';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { useAuth } from '../lib/auth.jsx';
import { Campo, useToast } from '../components/ui.jsx';

// T02 — Trocar senha (obrigatória no primeiro login)
export default function TrocarSenha() {
  const { trocarSenha, atualizar } = useAuth();
  const nav = useNavigate();
  const avisar = useToast();
  const [erroGeral, setErroGeral] = useState('');
  const { register, handleSubmit, setError, getValues, formState: { errors, isSubmitting } } = useForm();

  async function enviar(d) {
    setErroGeral('');
    try {
      await api.post('/me/senha', { senha_atual: d.senha_atual, nova_senha: d.nova_senha, confirmacao: d.confirmacao });
      await atualizar();
      avisar('Senha alterada com sucesso.');
      nav('/', { replace: true });
    } catch (e) {
      if (!aplicarErrosDeCampo(e, setError)) setErroGeral(e.message);
    }
  }

  return (
    <div style={{ maxWidth: 460 }}>
      <h1>Trocar senha</h1>
      {trocarSenha && <div className="alerta aviso">Por segurança, defina uma nova senha antes de continuar.</div>}
      {erroGeral && <div className="alerta erro" role="alert">{erroGeral}</div>}
      <form className="cartao grade-form" style={{ gridTemplateColumns: '1fr' }} onSubmit={handleSubmit(enviar)}>
        <Campo rotulo="Senha atual" obrigatorio erro={errors.senha_atual}>
          <input type="password" autoComplete="current-password" {...register('senha_atual', { required: 'obrigatório' })} />
        </Campo>
        <Campo rotulo="Nova senha (mínimo 8 caracteres)" obrigatorio erro={errors.nova_senha}>
          <input type="password" autoComplete="new-password" {...register('nova_senha', { required: 'obrigatório', minLength: { value: 8, message: 'mínimo de 8 caracteres' } })} />
        </Campo>
        <Campo rotulo="Confirmar nova senha" obrigatorio erro={errors.confirmacao}>
          <input type="password" autoComplete="new-password"
            {...register('confirmacao', { required: 'obrigatório', validate: (v) => v === getValues('nova_senha') || 'não confere com a nova senha' })} />
        </Campo>
        <button className="btn-primario" disabled={isSubmitting}>Salvar nova senha</button>
      </form>
    </div>
  );
}
