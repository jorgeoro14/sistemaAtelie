import { useState } from 'react';
import { useApi, useDebounce } from '../lib/useApi.js';
import { mascaraTelefone } from '../lib/format.js';
import ClienteForm from './ClienteForm.jsx';
import { Modal } from './ui.jsx';

// Seleção de cliente com busca + "cadastrar novo" sem sair da tela (não perde o que já foi digitado).
export default function ClienteSelect({ valor, aoMudar, erro, desabilitado }) {
  const [q, setQ] = useState('');
  const [novo, setNovo] = useState(false);
  const busca = useDebounce(q);
  const { dados } = useApi('/clientes', { q: busca, ativo: 1, por_pagina: 8 }, busca.length > 0 && !valor);

  if (valor) {
    return (
      <div className="alerta info" style={{ margin: 0, display: 'flex', justifyContent: 'space-between', gap: '.5rem', alignItems: 'center' }}>
        <span><strong>{valor.nome}</strong> · {mascaraTelefone(valor.telefone)}</span>
        {!desabilitado && <button type="button" className="btn-link" onClick={() => aoMudar(null)}>trocar</button>}
      </div>
    );
  }
  return (
    <div>
      <div style={{ display: 'flex', gap: '.5rem' }}>
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar cliente por nome ou telefone"
          className={erro ? 'invalido' : ''} aria-label="Buscar cliente" />
        <button type="button" className="btn-sec" onClick={() => setNovo(true)}>+ Novo</button>
      </div>
      {erro && <span className="erro-campo">{erro.message || erro}</span>}
      {dados?.dados?.length > 0 && (
        <div className="busca-resultados">
          {dados.dados.map((c) => (
            <button type="button" key={c.id} onClick={() => aoMudar(c)}>{c.nome} · {mascaraTelefone(c.telefone)}</button>
          ))}
        </div>
      )}
      {dados && busca && dados.dados.length === 0 && <p className="suave">Nenhum cliente encontrado. Use “+ Novo”.</p>}
      {novo && (
        <Modal titulo="Cadastrar novo cliente" aoFechar={() => setNovo(false)}>
          <ClienteForm aoCancelar={() => setNovo(false)} aoSalvar={(c) => { setNovo(false); aoMudar(c); }} />
        </Modal>
      )}
    </div>
  );
}
