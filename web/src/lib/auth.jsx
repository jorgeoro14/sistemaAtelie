import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api, definirAoPerderSessao, definirCsrf } from './api.js';

const AuthCtx = createContext(null);
export const useAuth = () => useContext(AuthCtx);

export function AuthProvider({ children }) {
  const [estado, setEstado] = useState({ carregando: true, usuario: null, trocarSenha: false });

  const aplicar = useCallback((me) => {
    definirCsrf(me?.csrf_token || null);
    setEstado({ carregando: false, usuario: me?.usuario || null, trocarSenha: !!me?.trocar_senha });
  }, []);

  const atualizar = useCallback(async () => {
    try { aplicar(await api.get('/me')); } catch { aplicar(null); }
  }, [aplicar]);

  useEffect(() => {
    definirAoPerderSessao(() => aplicar(null)); // 401 em qualquer rota → volta ao login
    atualizar();
  }, [atualizar, aplicar]);

  const entrar = async (usuario, senha) => { const me = await api.post('/login', { usuario, senha }); aplicar(me); return me; };
  const sair = async () => { try { await api.post('/logout'); } finally { aplicar(null); } };

  return <AuthCtx.Provider value={{ ...estado, entrar, sair, atualizar }}>{children}</AuthCtx.Provider>;
}
