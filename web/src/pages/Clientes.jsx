import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useApi, useDebounce } from '../lib/useApi.js';
import ClienteForm from '../components/ClienteForm.jsx';
import { CampoBusca, Carregando, ErroTela, Modal, Paginacao, Vazio } from '../components/ui.jsx';
import { linkWhatsApp, mascaraTelefone } from '../lib/format.js';

// T09 — Clientes (lista e cadastro)
export default function Clientes() {
  const nav = useNavigate();
  const [q, setQ] = useState('');
  const [pagina, setPagina] = useState(1);
  const [novo, setNovo] = useState(false);
  const busca = useDebounce(q);
  const { dados, erro, carregando, recarregar } = useApi('/clientes', { q: busca, pagina, por_pagina: 20 });

  return (
    <>
      <div className="pagina-cab"><h1>Clientes</h1><button className="btn-primario" onClick={() => setNovo(true)}>+ Novo cliente</button></div>
      <div className="filtros"><CampoBusca valor={q} aoMudar={(v) => { setQ(v); setPagina(1); }} placeholder="Buscar por nome ou telefone" /></div>
      {carregando && !dados ? <Carregando /> : erro ? <ErroTela erro={erro} aoTentar={recarregar} /> :
        dados.dados.length === 0 ? <Vazio>Nenhum cliente encontrado.</Vazio> : (
          <table className="tabela">
            <thead><tr><th>Nome</th><th>Telefone</th><th>Situação</th><th></th></tr></thead>
            <tbody>
              {dados.dados.map((c) => (
                <tr key={c.id} className="clicavel" onClick={() => nav(`/clientes/${c.id}`)}>
                  <td data-label="Nome"><strong>{c.nome}</strong></td>
                  <td data-label="Telefone">{mascaraTelefone(c.telefone)}</td>
                  <td data-label="Situação">{c.ativo ? 'Ativo' : <span className="selo cancelado">Inativo</span>}</td>
                  <td className="sem-rotulo" onClick={(e) => e.stopPropagation()}>
                    <a className="btn btn-sec btn-pequeno" href={linkWhatsApp(c.telefone)} target="_blank" rel="noreferrer">WhatsApp</a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      <Paginacao paginacao={dados?.paginacao} aoMudar={setPagina} />
      {novo && <Modal titulo="Novo cliente" aoFechar={() => setNovo(false)}>
        <ClienteForm aoCancelar={() => setNovo(false)} aoSalvar={(c) => { setNovo(false); nav(`/clientes/${c.id}`); }} />
      </Modal>}
    </>
  );
}
