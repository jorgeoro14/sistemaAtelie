import { useNavigate } from 'react-router-dom';

// T13 — Erro / sem permissão
const TEXTOS = {
  401: 'Você precisa entrar para ver esta página.',
  403: 'Você não tem permissão para acessar esta página.',
  404: 'Não encontramos a página que você procura.',
};

export default function Erro({ codigo = 404, mensagem }) {
  const nav = useNavigate();
  return (
    <div className="vazio">
      <h1>{codigo}</h1>
      <p>{mensagem || TEXTOS[codigo]}</p>
      <button className="btn-primario" onClick={() => nav(-1)}>Voltar</button>
    </div>
  );
}
