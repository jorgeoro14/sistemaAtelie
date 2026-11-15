import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/auth.jsx';
import { Campo } from '../components/ui.jsx';

// T01 — Login
export default function Login() {
  const { entrar } = useAuth();
  const nav = useNavigate();
  const [usuario, setUsuario] = useState('');
  const [senha, setSenha] = useState('');
  const [erro, setErro] = useState('');
  const [ocupado, setOcupado] = useState(false);

  async function enviar(e) {
    e.preventDefault();
    setErro(''); setOcupado(true);
    try {
      const me = await entrar(usuario.trim(), senha);
      nav(me.trocar_senha ? '/trocar-senha' : '/', { replace: true });
    } catch (err) {
      setErro(err.message);
    } finally { setOcupado(false); }
  }

  return (
    <div className="login">
      <form className="cartao" onSubmit={enviar}>
        <img src={`${import.meta.env.BASE_URL}logo.jpg`} alt="Bella Costura" />
        <h1>Bella Costura</h1>
        <p className="suave" style={{ textAlign: 'center' }}>Gestão do ateliê</p>
        {erro && <div className="alerta erro" role="alert">{erro}</div>}
        <div className="grade-form" style={{ gridTemplateColumns: '1fr' }}>
          <Campo rotulo="Usuário" obrigatorio>
            <input value={usuario} onChange={(e) => setUsuario(e.target.value)} autoComplete="username" autoFocus required />
          </Campo>
          <Campo rotulo="Senha" obrigatorio>
            <input type="password" value={senha} onChange={(e) => setSenha(e.target.value)} autoComplete="current-password" required />
          </Campo>
          <button className="btn-primario" disabled={ocupado}>{ocupado ? 'Entrando…' : 'Entrar'}</button>
        </div>
      </form>
    </div>
  );
}
