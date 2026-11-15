import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { ROTULO_PRIORIDADE, ROTULO_STATUS } from '../lib/format.js';
import { mascaraTelefone } from '../lib/format.js';

/* ---------- Selos: sempre texto + cor ---------- */
export const SeloStatus = ({ status }) => <span className={`selo ${status}`}>{ROTULO_STATUS[status] || status}</span>;
export const SeloPrioridade = ({ prioridade }) =>
  prioridade === 'normal' ? null : <span className={`selo ${prioridade}`}>{ROTULO_PRIORIDADE[prioridade]}</span>;
export const SeloPago = ({ situacao }) => (
  <span className={`selo ${situacao === 'pago' ? 'pago' : 'pendente'}`}>{situacao === 'pago' ? 'Pago' : 'Pendente'}</span>
);
export function SelosOs({ os }) {
  return (
    <span className="selos">
      <SeloStatus status={os.status} />
      <SeloPrioridade prioridade={os.prioridade} />
      {os.atrasada && <span className="selo atrasada">Atrasada</span>}
    </span>
  );
}

/* ---------- Estados de tela ---------- */
export const Carregando = () => <div className="vazio" role="status">Carregando…</div>;
export const Vazio = ({ children = 'Nada por aqui ainda.' }) => <div className="vazio">{children}</div>;
export function ErroTela({ erro, aoTentar }) {
  return (
    <div className="alerta erro" role="alert">
      {erro?.message || 'Algo deu errado.'}{' '}
      {aoTentar && <button className="btn-link" onClick={aoTentar}>Tentar de novo</button>}
    </div>
  );
}

/* ---------- Campo com rótulo e erro ---------- */
export function Campo({ rotulo, obrigatorio, erro, children }) {
  return (
    <label className="campo">
      <span>{rotulo}{obrigatorio && <span className="req"> *</span>}</span>
      {children}
      {erro && <span className="erro-campo" role="alert">{erro.message || erro}</span>}
    </label>
  );
}

/* ---------- Modal ---------- */
export function Modal({ titulo, aoFechar, largo, children }) {
  useEffect(() => {
    const f = (e) => e.key === 'Escape' && aoFechar();
    window.addEventListener('keydown', f);
    return () => window.removeEventListener('keydown', f);
  }, [aoFechar]);
  return (
    <div className="fundo-modal" onMouseDown={(e) => e.target === e.currentTarget && aoFechar()}>
      <div className={`modal ${largo ? 'largo' : ''}`} role="dialog" aria-modal="true" aria-label={titulo}>
        <h2>{titulo}</h2>
        {children}
      </div>
    </div>
  );
}

/* Confirmação antes de ações destrutivas */
export function Confirmar({ titulo, mensagem, rotuloConfirmar = 'Confirmar', perigo, aoConfirmar, aoFechar }) {
  const [ocupado, setOcupado] = useState(false);
  return (
    <Modal titulo={titulo} aoFechar={aoFechar}>
      <p>{mensagem}</p>
      <div className="modal-acoes">
        <button className="btn-sec" onClick={aoFechar} disabled={ocupado}>Voltar</button>
        <button
          className={perigo ? 'btn-perigo' : 'btn-primario'}
          disabled={ocupado}
          onClick={async () => { setOcupado(true); try { await aoConfirmar(); } finally { setOcupado(false); } }}
        >
          {rotuloConfirmar}
        </button>
      </div>
    </Modal>
  );
}

/* ---------- Toasts ---------- */
const ToastCtx = createContext(() => {});
export const useToast = () => useContext(ToastCtx);
export function ToastProvider({ children }) {
  const [itens, setItens] = useState([]);
  const avisar = useCallback((texto, tipo = 'ok') => {
    const id = Math.random();
    setItens((l) => [...l, { id, texto, tipo }]);
    setTimeout(() => setItens((l) => l.filter((i) => i.id !== id)), 5000);
  }, []);
  return (
    <ToastCtx.Provider value={avisar}>
      {children}
      <div className="toasts" aria-live="polite">
        {itens.map((i) => <div key={i.id} className={`toast ${i.tipo}`}>{i.texto}</div>)}
      </div>
    </ToastCtx.Provider>
  );
}

/* ---------- Paginação ---------- */
export function Paginacao({ paginacao, aoMudar }) {
  if (!paginacao || paginacao.total_paginas <= 1) return null;
  const { pagina, total_paginas: total } = paginacao;
  return (
    <nav className="paginacao" aria-label="Paginação">
      <button className="btn-sec btn-pequeno" disabled={pagina <= 1} onClick={() => aoMudar(pagina - 1)}>← Anterior</button>
      <span>Página {pagina} de {total}</span>
      <button className="btn-sec btn-pequeno" disabled={pagina >= total} onClick={() => aoMudar(pagina + 1)}>Próxima →</button>
    </nav>
  );
}

/* ---------- Campos especiais ---------- */
export function CampoBusca({ valor, aoMudar, placeholder = 'Buscar…' }) {
  return (
    <input type="search" value={valor} onChange={(e) => aoMudar(e.target.value)} placeholder={placeholder} aria-label={placeholder} />
  );
}

// Telefone com máscara (45) 99999-8888; o valor do formulário fica mascarado, e é limpo ao enviar.
export function CampoTelefone({ value, onChange, ...resto }) {
  return (
    <input
      type="tel" inputMode="tel" placeholder="(45) 99999-8888" autoComplete="off"
      value={mascaraTelefone(value || '')} onChange={(e) => onChange(mascaraTelefone(e.target.value))} {...resto}
    />
  );
}

// Dinheiro: o usuário digita em reais ("35,00"); o front converte para centavos ao enviar.
export function CampoDinheiro({ value, onChange, ...resto }) {
  return (
    <input
      type="text" inputMode="decimal" placeholder="0,00" autoComplete="off"
      value={value ?? ''} onChange={(e) => onChange(e.target.value.replace(/[^\d.,]/g, ''))} {...resto}
    />
  );
}
