import { Navigate, Route, Routes, useLocation } from 'react-router-dom';
import { useAuth } from './lib/auth.jsx';
import Layout from './components/Layout.jsx';
import { Carregando } from './components/ui.jsx';
import Login from './pages/Login.jsx';
import TrocarSenha from './pages/TrocarSenha.jsx';
import Inicio from './pages/Inicio.jsx';
import Agenda from './pages/Agenda.jsx';
import ListaOs from './pages/ListaOs.jsx';
import FormOs from './pages/FormOs.jsx';
import DetalheOs from './pages/DetalheOs.jsx';
import Clientes from './pages/Clientes.jsx';
import DetalheCliente from './pages/DetalheCliente.jsx';
import Servicos from './pages/Servicos.jsx';
import Costureiras from './pages/Costureiras.jsx';
import Usuarios from './pages/Usuarios.jsx';
import Desempenho from './pages/Desempenho.jsx';
import Erro from './pages/Erro.jsx';

// Rotas protegidas: sem sessão → login; senha provisória → troca obrigatória.
function Protegida({ children }) {
  const { carregando, usuario, trocarSenha } = useAuth();
  const local = useLocation();
  if (carregando) return <Carregando />;
  if (!usuario) return <Navigate to="/login" replace state={{ de: local.pathname + local.search }} />;
  if (trocarSenha && local.pathname !== '/trocar-senha') return <Navigate to="/trocar-senha" replace />;
  return <Layout>{children}</Layout>;
}

export default function App() {
  const { carregando, usuario } = useAuth();
  if (carregando) return <Carregando />;
  return (
    <Routes>
      <Route path="/login" element={usuario ? <Navigate to="/" replace /> : <Login />} />
      <Route path="/trocar-senha" element={<Protegida><TrocarSenha /></Protegida>} />
      <Route path="/" element={<Protegida><Inicio /></Protegida>} />
      <Route path="/agenda" element={<Protegida><Agenda /></Protegida>} />
      <Route path="/os" element={<Protegida><ListaOs /></Protegida>} />
      <Route path="/os/nova" element={<Protegida><FormOs /></Protegida>} />
      <Route path="/os/:id" element={<Protegida><DetalheOs /></Protegida>} />
      <Route path="/os/:id/editar" element={<Protegida><FormOs /></Protegida>} />
      <Route path="/clientes" element={<Protegida><Clientes /></Protegida>} />
      <Route path="/clientes/:id" element={<Protegida><DetalheCliente /></Protegida>} />
      <Route path="/servicos" element={<Protegida><Servicos /></Protegida>} />
      <Route path="/costureiras" element={<Protegida><Costureiras /></Protegida>} />
      <Route path="/usuarios" element={<Protegida><Usuarios /></Protegida>} />
      <Route path="/desempenho" element={<Protegida><Desempenho /></Protegida>} />
      <Route path="*" element={usuario ? <Protegida><Erro codigo={404} /></Protegida> : <Navigate to="/login" replace />} />
    </Routes>
  );
}
