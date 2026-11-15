import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { useAuth } from '../lib/auth.jsx';
import { useApi } from '../lib/useApi.js';
import { Campo, Carregando, Confirmar, ErroTela, Modal, Vazio, useToast } from '../components/ui.jsx';
import Erro from './Erro.jsx';

function FormUsuario({ inicial, aoSalvar, aoCancelar }) {
  const [erroGeral, setErroGeral] = useState('');
  const { register, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: { nome: inicial?.nome || '', usuario: inicial?.usuario || '', senha: '', perfil: 'admin' },
  });
  async function enviar(d) {
    const corpo = { nome: d.nome, usuario: d.usuario, perfil: d.perfil, ...(inicial ? {} : { senha: d.senha }) };
    try { aoSalvar(inicial ? await api.put(`/usuarios/${inicial.id}`, { ...corpo, ativo: inicial.ativo }) : await api.post('/usuarios', corpo)); }
    catch (e) { if (!aplicarErrosDeCampo(e, setError)) setErroGeral(e.message); }
  }
  return (
    <form onSubmit={handleSubmit(enviar)}>
      {erroGeral && <div className="alerta erro">{erroGeral}</div>}
      <div className="grade-form" style={{ gridTemplateColumns: '1fr' }}>
        <Campo rotulo="Nome" obrigatorio erro={errors.nome}><input {...register('nome', { required: 'obrigatório' })} autoFocus /></Campo>
        <Campo rotulo="Usuário (login)" obrigatorio erro={errors.usuario}><input autoComplete="off" {...register('usuario', { required: 'obrigatório', minLength: { value: 3, message: 'mínimo de 3 caracteres' } })} /></Campo>
        {!inicial && <Campo rotulo="Senha provisória (mín. 8)" obrigatorio erro={errors.senha}>
          <input type="password" autoComplete="new-password" {...register('senha', { required: 'obrigatório', minLength: { value: 8, message: 'mínimo de 8 caracteres' } })} /></Campo>}
        <Campo rotulo="Perfil"><select {...register('perfil')}><option value="admin">Administrador</option></select></Campo>
      </div>
      <div className="modal-acoes"><button type="button" className="btn-sec" onClick={aoCancelar}>Cancelar</button><button className="btn-primario" disabled={isSubmitting}>Salvar</button></div>
    </form>
  );
}

function FormRedefinir({ usuario, aoSalvar, aoCancelar }) {
  const [erroGeral, setErroGeral] = useState('');
  const { register, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm();
  async function enviar(d) {
    try { await api.post(`/usuarios/${usuario.id}/redefinir-senha`, { nova_senha: d.nova_senha }); aoSalvar(); }
    catch (e) { if (!aplicarErrosDeCampo(e, setError)) setErroGeral(e.message); }
  }
  return (
    <form onSubmit={handleSubmit(enviar)}>
      {erroGeral && <div className="alerta erro">{erroGeral}</div>}
      <p className="suave">{usuario.nome} será obrigado(a) a trocar esta senha no próximo login.</p>
      <Campo rotulo="Nova senha provisória (mín. 8)" obrigatorio erro={errors.nova_senha}>
        <input type="password" autoComplete="new-password" {...register('nova_senha', { required: 'obrigatório', minLength: { value: 8, message: 'mínimo de 8 caracteres' } })} autoFocus /></Campo>
      <div className="modal-acoes"><button type="button" className="btn-sec" onClick={aoCancelar}>Cancelar</button><button className="btn-primario" disabled={isSubmitting}>Redefinir</button></div>
    </form>
  );
}

// T12 — Usuários (somente admin)
export default function Usuarios() {
  const { usuario: eu } = useAuth();
  const avisar = useToast();
  const [modal, setModal] = useState(null);
  const [erroAcao, setErroAcao] = useState('');
  const { dados, erro, carregando, recarregar } = useApi('/usuarios', { por_pagina: 100 });

  if (eu?.perfil !== 'admin') return <Erro codigo={403} />;

  async function alternar(u) {
    setErroAcao('');
    try {
      await api.put(`/usuarios/${u.id}`, { nome: u.nome, usuario: u.usuario, perfil: u.perfil, ativo: !u.ativo });
      avisar(u.ativo ? 'Usuário desativado.' : 'Usuário reativado.'); recarregar();
    } catch (e) { setErroAcao(e.message); }
    setModal(null);
  }
  return (
    <>
      <div className="pagina-cab"><h1>Usuários</h1><button className="btn-primario" onClick={() => setModal({ tipo: 'form' })}>+ Novo usuário</button></div>
      {erroAcao && <div className="alerta erro" role="alert">{erroAcao}</div>}
      {carregando && !dados ? <Carregando /> : erro ? <ErroTela erro={erro} aoTentar={recarregar} /> :
        dados.dados.length === 0 ? <Vazio>Nenhum usuário.</Vazio> : (
          <table className="tabela">
            <thead><tr><th>Nome</th><th>Usuário</th><th>Perfil</th><th>Situação</th><th></th></tr></thead>
            <tbody>{dados.dados.map((u) => (
              <tr key={u.id}>
                <td data-label="Nome"><strong>{u.nome}</strong></td><td data-label="Usuário">{u.usuario}</td><td data-label="Perfil">Administrador</td>
                <td data-label="Situação">{u.ativo ? 'Ativo' : <span className="selo cancelado">Inativo</span>}</td>
                <td className="sem-rotulo"><div className="acoes">
                  <button className="btn-sec btn-pequeno" onClick={() => setModal({ tipo: 'form', item: u })}>Editar</button>
                  <button className="btn-sec btn-pequeno" onClick={() => setModal({ tipo: 'senha', item: u })}>Redefinir senha</button>
                  {u.ativo ? <button className="btn-perigo btn-pequeno" onClick={() => setModal({ tipo: 'inativar', item: u })}>Desativar</button>
                    : <button className="btn-sec btn-pequeno" onClick={() => alternar(u)}>Reativar</button>}
                </div></td>
              </tr>))}</tbody>
          </table>)}
      {modal?.tipo === 'form' && <Modal titulo={modal.item ? 'Editar usuário' : 'Novo usuário'} aoFechar={() => setModal(null)}>
        <FormUsuario inicial={modal.item} aoCancelar={() => setModal(null)} aoSalvar={() => { avisar('Usuário salvo.'); setModal(null); recarregar(); }} /></Modal>}
      {modal?.tipo === 'senha' && <Modal titulo="Redefinir senha" aoFechar={() => setModal(null)}>
        <FormRedefinir usuario={modal.item} aoCancelar={() => setModal(null)} aoSalvar={() => { avisar('Senha redefinida.'); setModal(null); }} /></Modal>}
      {modal?.tipo === 'inativar' && <Confirmar perigo titulo="Desativar usuário" mensagem={`Desativar ${modal.item.nome}? Ele(a) não conseguirá mais entrar.`}
        rotuloConfirmar="Desativar" aoFechar={() => setModal(null)} aoConfirmar={() => alternar(modal.item)} />}
    </>
  );
}
