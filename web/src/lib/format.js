const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

// Dinheiro trafega em centavos inteiros; só aqui é formatado em reais.
export const reais = (centavos) => brl.format((centavos || 0) / 100);

// "35,50" | "35.5" | "1.234,50" -> 3550 (centavos). Retorna null se inválido.
export function paraCentavos(texto) {
  if (texto === null || texto === undefined) return null;
  let s = String(texto).trim().replace(/[R$\s]/g, '');
  if (s === '') return null;
  if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
  if (!/^\d+(\.\d{1,2})?$/.test(s)) return null;
  const [int, dec = ''] = s.split('.');
  return parseInt(int, 10) * 100 + parseInt(dec.padEnd(2, '0'), 10);
}

export const deCentavos = (c) =>
  c === null || c === undefined ? '' : (c / 100).toFixed(2).replace('.', ',');

export const soDigitos = (t) => (t || '').replace(/\D/g, '');

export function mascaraTelefone(valor) {
  const d = soDigitos(valor).slice(0, 11);
  if (d.length <= 2) return d.length ? `(${d}` : '';
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}

export const linkWhatsApp = (tel) => `https://wa.me/55${soDigitos(tel)}`;

// 'YYYY-MM-DD' -> 'DD/MM/AAAA' sem passar por Date (evita erro de fuso).
export const dataBR = (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('/') : '—');

const pad = (n) => String(n).padStart(2, '0');
export const hojeISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};
export const paraISO = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
export const maisDias = (iso, n) => {
  const [a, m, d] = iso.split('-').map(Number);
  return paraISO(new Date(a, m - 1, d + n));
};

export const ROTULO_STATUS = {
  recebido: 'Recebido', em_andamento: 'Em andamento', pronto: 'Pronto',
  entregue: 'Entregue', cancelado: 'Cancelado',
};
export const ROTULO_PRIORIDADE = { normal: 'Normal', alta: 'Alta', urgente: 'Urgente' };
export const ROTULO_FORMA = {
  dinheiro: 'Dinheiro', pix: 'Pix', cartao_debito: 'Cartão de débito',
  cartao_credito: 'Cartão de crédito', outro: 'Outro',
};
export const ROTULO_CATEGORIA = {
  conserto: 'Conserto', sob_medida: 'Sob medida', ajuste: 'Ajuste', outro: 'Outro',
};
export const ROTULO_TIPO_AGENDA = {
  prova: 'Prova', medidas: 'Medidas', retirada: 'Retirada', outro: 'Outro',
};
