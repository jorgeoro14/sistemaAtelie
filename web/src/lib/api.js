// Cliente HTTP único: fetch same-origin, injeta X-CSRF-Token, converte erros da API.
// Em subpasta (ex.: /atelie/) o Vite define BASE_URL; na raiz é '/'.
const BASE = import.meta.env.BASE_URL.replace(/\/$/, '');
let csrf = null;
let aoPerderSessao = null;

export class ApiError extends Error {
  constructor(status, corpo) {
    super(corpo?.mensagem || 'Não foi possível concluir a ação.');
    this.status = status;
    this.codigo = corpo?.erro || 'erro';
    this.campos = corpo?.campos || {};
  }
}

export const definirCsrf = (t) => { csrf = t; };
export const definirAoPerderSessao = (fn) => { aoPerderSessao = fn; };

export function qs(params = {}) {
  const p = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '' && v !== false) p.set(k, v === true ? '1' : String(v));
  });
  const s = p.toString();
  return s ? `?${s}` : '';
}

async function requisitar(metodo, caminho, corpo) {
  const headers = { Accept: 'application/json' };
  if (corpo !== undefined) headers['Content-Type'] = 'application/json';
  if (metodo !== 'GET' && csrf) headers['X-CSRF-Token'] = csrf;

  let res;
  try {
    res = await fetch(`${BASE}/api${caminho}`, {
      method: metodo,
      headers,
      credentials: 'same-origin',
      body: corpo !== undefined ? JSON.stringify(corpo) : undefined,
    });
  } catch {
    throw new ApiError(0, { erro: 'sem_conexao', mensagem: 'Sem conexão com o servidor. Verifique a internet.' });
  }

  let dados = null;
  if (res.status !== 204) {
    try { dados = await res.json(); } catch { dados = null; }
  }
  if (!res.ok) {
    const erro = new ApiError(res.status, dados);
    if (res.status === 401 && !['/login', '/me'].includes(caminho) && aoPerderSessao) aoPerderSessao();
    throw erro;
  }
  return dados;
}

export const api = {
  get: (c, params) => requisitar('GET', c + qs(params)),
  post: (c, corpo = {}) => requisitar('POST', c, corpo),
  put: (c, corpo) => requisitar('PUT', c, corpo),
  patch: (c, corpo) => requisitar('PATCH', c, corpo),
  del: (c) => requisitar('DELETE', c),
};

// Joga os erros por campo (422) dentro do React Hook Form.
export function aplicarErrosDeCampo(erro, setError, mapa = (k) => k) {
  if (!(erro instanceof ApiError)) return false;
  const chaves = Object.keys(erro.campos);
  chaves.forEach((k) => setError(mapa(k), { type: 'servidor', message: erro.campos[k] }));
  return chaves.length > 0;
}
