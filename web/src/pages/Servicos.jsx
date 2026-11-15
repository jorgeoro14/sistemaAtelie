import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { useApi, useDebounce } from '../lib/useApi.js';
import { Campo, CampoBusca, CampoDinheiro, Carregando, Confirmar, ErroTela, Modal, Paginacao, Vazio, useToast } from '../components/ui.jsx';
import { deCentavos, paraCentavos, reais, ROTULO_CATEGORIA } from '../lib/format.js';

function FormServico({ inicial, aoSalvar, aoCancelar }) {
  const [erroGeral, setErroGeral] = useState('');
  const { register, control, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: { nome: inicial?.nome || '', categoria: inicial?.categoria || 'conserto', valor: deCentavos(inicial?.valor_padrao_centavos) },
  });
  async function enviar(d) {
    const corpo = { nome: d.nome, categoria: d.categoria, valor_padrao_centavos: paraCentavos(d.valor) };
    try { aoSalvar(inicial ? await api.put(`/servicos/${inicial.id}`, corpo) : await api.post('/servicos', corpo)); }
    catch (e) { if (!aplicarErrosDeCampo(e, setError, (k) => (k === 'valor_padrao_centavos' ? 'valor' : k))) setErroGeral(e.message); }
  }
  return (
    <form onSubmit={handleSubmit(enviar)}>
      {erroGeral && <div className="alerta erro">{erroGeral}</div>}
      <div className="grade-form" style={{ gridTemplateColumns: '1fr' }}>
        <Campo rotulo="Nome" obrigatorio erro={errors.nome}><input {...register('nome', { required: 'obrigatório' })} autoFocus /></Campo>
        <Campo rotulo="Categoria" erro={errors.categoria}>
          <select {...register('categoria')}>{Object.entries(ROTULO_CATEGORIA).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select>
        </Campo>
        <Campo rotulo="Valor padrão (opcional, é só sugestão)" erro={errors.valor}>
          <Controller control={control} name="valor" rules={{ validate: (v) => !v || paraCentavos(v) !== null || 'valor inválido' }}
            render={({ field: { ref: _ref, ...campo } }) => <CampoDinheiro {...campo} />} />
        </Campo>
      </div>
      <div className="modal-acoes"><button type="button" className="btn-sec" onClick={aoCancelar}>Cancelar</button><button className="btn-primario" disabled={isSubmitting}>Salvar</button></div>
    </form>
  );
}

// T10 — Catálogo de serviços
export default function Servicos() {
  const avisar = useToast();
  const [q, setQ] = useState('');
  const [pagina, setPagina] = useState(1);
  const [modal, setModal] = useState(null); // {tipo:'form'|'inativar', item}
  const busca = useDebounce(q);
  const { dados, erro, carregando, recarregar } = useApi('/servicos', { q: busca, pagina, por_pagina: 20 });

  async function alternar(s) {
    await api.put(`/servicos/${s.id}`, { nome: s.nome, categoria: s.categoria, valor_padrao_centavos: s.valor_padrao_centavos, ativo: !s.ativo });
    avisar(s.ativo ? 'Serviço inativado.' : 'Serviço reativado.'); setModal(null); recarregar();
  }
  return (
    <>
      <div className="pagina-cab"><h1>Catálogo de serviços</h1><button className="btn-primario" onClick={() => setModal({ tipo: 'form' })}>+ Novo serviço</button></div>
      <div className="filtros"><CampoBusca valor={q} aoMudar={(v) => { setQ(v); setPagina(1); }} placeholder="Buscar serviço" /></div>
      {carregando && !dados ? <Carregando /> : erro ? <ErroTela erro={erro} aoTentar={recarregar} /> :
        dados.dados.length === 0 ? <Vazio>Nenhum serviço cadastrado.</Vazio> : (
          <table className="tabela">
            <thead><tr><th>Nome</th><th>Categoria</th><th className="num">Valor padrão</th><th>Situação</th><th></th></tr></thead>
            <tbody>{dados.dados.map((s) => (
              <tr key={s.id}>
                <td data-label="Nome"><strong>{s.nome}</strong></td><td data-label="Categoria">{ROTULO_CATEGORIA[s.categoria]}</td>
                <td data-label="Valor padrão" className="num">{s.valor_padrao_centavos !== null ? reais(s.valor_padrao_centavos) : '—'}</td>
                <td data-label="Situação">{s.ativo ? 'Ativo' : <span className="selo cancelado">Inativo</span>}</td>
                <td className="sem-rotulo"><div className="acoes">
                  <button className="btn-sec btn-pequeno" onClick={() => setModal({ tipo: 'form', item: s })}>Editar</button>
                  {s.ativo ? <button className="btn-perigo btn-pequeno" onClick={() => setModal({ tipo: 'inativar', item: s })}>Inativar</button>
                    : <button className="btn-sec btn-pequeno" onClick={() => alternar(s)}>Reativar</button>}
                </div></td>
              </tr>))}</tbody>
          </table>)}
      <Paginacao paginacao={dados?.paginacao} aoMudar={setPagina} />
      {modal?.tipo === 'form' && <Modal titulo={modal.item ? 'Editar serviço' : 'Novo serviço'} aoFechar={() => setModal(null)}>
        <FormServico inicial={modal.item} aoCancelar={() => setModal(null)} aoSalvar={() => { avisar('Serviço salvo.'); setModal(null); recarregar(); }} /></Modal>}
      {modal?.tipo === 'inativar' && <Confirmar perigo titulo="Inativar serviço" mensagem={`Inativar “${modal.item.nome}”? Ele some das novas OS, mas continua nas antigas.`}
        rotuloConfirmar="Inativar" aoFechar={() => setModal(null)} aoConfirmar={() => alternar(modal.item)} />}
    </>
  );
}
