import { NavLink, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../lib/auth.jsx';

const ITENS = [
  { para: '/', rotulo: 'Início', icone: '🏠', fim: true },
  { para: '/agenda', rotulo: 'Agenda', icone: '📅' },
  { para: '/os', rotulo: 'Ordens', icone: '🧵' },
  { para: '/clientes', rotulo: 'Clientes', icone: '👥' },
  { para: '/servicos', rotulo: 'Serviços', icone: '✂️' },
  { para: '/costureiras', rotulo: 'Costureiras', icone: '🪡' },
  { para: '/desempenho', rotulo: 'Desempenho', icone: '📊', admin: true },
  { para: '/usuarios', rotulo: 'Usuários', icone: '🔑', admin: true },
];

export default function Layout({ children }) {
  const { usuario, trocarSenha, sair } = useAuth();
  const local = useLocation();
  const forcarTroca = trocarSenha && local.pathname === '/trocar-senha';
  const ehAdmin = usuario?.perfil === 'admin';

  return (
    <div className="app">
      <nav className="menu" aria-label="Menu principal">
        <Link to="/" className="marca">
          <img src={`${import.meta.env.BASE_URL}logo.jpg`} alt="" /> Bella Costura
        </Link>
        {!forcarTroca && ITENS.filter((i) => !i.admin || ehAdmin).map((i) => (
          <NavLink key={i.para} to={i.para} end={i.fim} className={({ isActive }) => `item ${isActive ? 'ativo' : ''}`}>
            <span aria-hidden="true">{i.icone}</span> {i.rotulo}
          </NavLink>
        ))}
        <div className="espaco" />
        <button className="item" onClick={sair}><span aria-hidden="true">🚪</span> Sair</button>
      </nav>
      <div className="conteudo">
        <header className="topo">
          <span className="suave">Ateliê de costura</span>
          <div className="usuario">
            <span>Olá, <strong>{usuario?.nome}</strong></span>
            {!forcarTroca && <Link to="/trocar-senha" className="btn-link">Trocar senha</Link>}
          </div>
        </header>
        <main className="pagina">{children}</main>
      </div>
    </div>
  );
}
