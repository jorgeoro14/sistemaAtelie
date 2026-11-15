import { useCallback, useEffect, useRef, useState } from 'react';
import { api } from './api.js';

// Busca dados da API e controla carregando / erro (estados exigidos em todas as telas).
export function useApi(caminho, params, ativo = true) {
  const [dados, setDados] = useState(null);
  const [erro, setErro] = useState(null);
  const [carregando, setCarregando] = useState(ativo);
  const chave = caminho + JSON.stringify(params || {});
  const contador = useRef(0);

  const carregar = useCallback(async () => {
    if (!ativo) return;
    const meu = ++contador.current;
    setCarregando(true);
    try {
      const r = await api.get(caminho, params);
      if (meu === contador.current) { setDados(r); setErro(null); }
    } catch (e) {
      if (meu === contador.current) setErro(e);
    } finally {
      if (meu === contador.current) setCarregando(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [chave, ativo]);

  useEffect(() => { carregar(); }, [carregar]);
  return { dados, erro, carregando, recarregar: carregar };
}

export function useDebounce(valor, ms = 350) {
  const [v, setV] = useState(valor);
  useEffect(() => {
    const t = setTimeout(() => setV(valor), ms);
    return () => clearTimeout(t);
  }, [valor, ms]);
  return v;
}
