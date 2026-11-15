import { useEffect, useState } from 'react';
import { Controller, useFieldArray, useForm } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';
import { api, aplicarErrosDeCampo } from '../lib/api.js';
import { useApi } from '../lib/useApi.js';
import { deCentavos, hojeISO, maisDias, paraCentavos, reais } from '../lib/format.js';
import ClienteSelect from '../components/ClienteSelect.jsx';
import { Campo, CampoDinheiro, Carregando, ErroTela, useToast } from '../components/ui.jsx';

const linhaVazia = { servico_id: '', descricao: '', valor: '' };

// T07 — Nova / editar OS (a tela mais usada)
export default function FormOs() {
  const { id } = useParams();
  const editando = !!id;
  const nav = useNavigate();
  const avisar = useToast();
  const [erroGeral, setErroGeral] = useState('');
  const [cliente, setCliente] = useState(null);

  const { dados: servicos } = useApi('/servicos', { ativo: 1, por_pagina: 100 });
  const { dados: costureiras } = useApi('/costureiras', { ativa: 1, por_pagina: 100 });
  const { dados: os, erro: erroOs, carregando } = useApi(`/os/${id}`, null, editando);

  const { register, control, handleSubmit, setValue, getValues, reset, watch, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: {
      costureira_id: '', prioridade: 'normal', data_recebimento: hojeISO(), data_prevista: maisDias(hojeISO(), 7),
      observacoes: '', itens: [{ ...linhaVazia }],
    },
  });
  const { fields, append, remove } = useFieldArray({ control, name: 'itens' });

  useEffect(() => {
    if (!os) return;
    setCliente(os.cliente);
    reset({
      costureira_id: os.costureira_id || '', prioridade: os.prioridade, data_recebimento: os.data_recebimento,
      data_prevista: os.data_prevista, observacoes: os.observacoes || '',
      itens: os.itens.map((i) => ({ servico_id: String(i.servico_id), descricao: i.descricao, valor: deCentavos(i.valor_centavos) })),
    });
  }, [os, reset]);

  const itens = watch('itens');
  const totalCentavos = (itens || []).reduce((s, i) => s + (paraCentavos(i.valor) || 0), 0); // total ao vivo (o back recalcula)

  function escolherServico(indice, servicoId) {
    const s = servicos?.dados.find((x) => String(x.id) === String(servicoId));
    if (!s) return;
    if (s.valor_padrao_centavos !== null) setValue(`itens.${indice}.valor`, deCentavos(s.valor_padrao_centavos)); // só sugestão
    if (!getValues(`itens.${indice}.descricao`)) setValue(`itens.${indice}.descricao`, s.nome);
  }

  async function enviar(d) {
    setErroGeral('');
    if (!cliente) { setError('cliente_id', { message: 'escolha o cliente' }); return; }
    const corpo = {
      cliente_id: cliente.id,
      costureira_id: d.costureira_id ? Number(d.costureira_id) : null,
      prioridade: d.prioridade,
      data_recebimento: d.data_recebimento,
      data_prevista: d.data_prevista,
      observacoes: d.observacoes || null,
      itens: d.itens.map((i) => ({ servico_id: Number(i.servico_id) || 0, descricao: i.descricao, valor_centavos: paraCentavos(i.valor) ?? -1 })),
    };
    try {
      const r = editando ? await api.put(`/os/${id}`, corpo) : await api.post('/os', corpo);
      avisar(`OS #${r.numero} salva. Total ${reais(r.total_centavos)}.`);
      nav(`/os/${r.id}`);
    } catch (e) {
      const mapa = (k) => k.replace(/\.valor_centavos$/, '.valor');
      if (!aplicarErrosDeCampo(e, setError, mapa)) setErroGeral(e.message);
      else setErroGeral('Confira os campos destacados.');
    }
  }

  if (editando && carregando) return <Carregando />;
  if (editando && erroOs) return <ErroTela erro={erroOs} />;

  return (
    <form onSubmit={handleSubmit(enviar)}>
      <div className="pagina-cab"><h1>{editando ? `Editar OS #${os?.numero ?? ''}` : 'Nova ordem de serviço'}</h1></div>
      {editando && os?.paga && <div className="alerta aviso">OS paga não pode ser editada. Estorne o pagamento na tela da OS para alterar.</div>}
      {erroGeral && <div className="alerta erro" role="alert">{erroGeral}</div>}

      <section className="cartao">
        <h2>Cliente e prazos</h2>
        <div className="grade-form">
          <Campo rotulo="Cliente" obrigatorio><ClienteSelect valor={cliente} aoMudar={setCliente} erro={errors.cliente_id} /></Campo>
          <Campo rotulo="Costureira (opcional)" erro={errors.costureira_id}>
            <select {...register('costureira_id')}>
              <option value="">Sem costureira</option>
              {costureiras?.dados.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
              {os?.costureira && !costureiras?.dados.some((c) => c.id === os.costureira.id) && <option value={os.costureira.id}>{os.costureira.nome} (inativa)</option>}
            </select>
          </Campo>
          <Campo rotulo="Prioridade" erro={errors.prioridade}>
            <select {...register('prioridade')}>
              <option value="normal">Normal</option><option value="alta">Alta</option><option value="urgente">Urgente</option>
            </select>
          </Campo>
          <Campo rotulo="Data de recebimento" obrigatorio erro={errors.data_recebimento}>
            <input type="date" {...register('data_recebimento', { required: 'obrigatória' })} />
          </Campo>
          <Campo rotulo="Entrega prevista" obrigatorio erro={errors.data_prevista}>
            <input type="date" {...register('data_prevista', { required: 'obrigatória', validate: (v) => v >= getValues('data_recebimento') || 'não pode ser antes do recebimento' })} />
          </Campo>
        </div>
      </section>

      <section className="cartao">
        <h2>Serviços</h2>
        {errors.itens?.message && <div className="erro-campo">{errors.itens.message}</div>}
        {fields.map((f, i) => (
          <div className="item-os" key={f.id}>
            <Campo rotulo="Tipo de serviço" obrigatorio erro={errors.itens?.[i]?.servico_id}>
              <select {...register(`itens.${i}.servico_id`, { required: 'escolha o tipo', onChange: (e) => escolherServico(i, e.target.value) })}>
                <option value="">Escolha…</option>
                {servicos?.dados.map((s) => <option key={s.id} value={s.id}>{s.nome}</option>)}
                {os?.itens[i] && !servicos?.dados.some((s) => s.id === os.itens[i].servico_id) &&
                  <option value={os.itens[i].servico_id}>{os.itens[i].servico_nome} (inativo)</option>}
              </select>
            </Campo>
            <Campo rotulo="O que será feito" obrigatorio erro={errors.itens?.[i]?.descricao}>
              <input {...register(`itens.${i}.descricao`, { required: 'obrigatório', minLength: { value: 2, message: 'muito curto' } })} />
            </Campo>
            <Campo rotulo="Valor (R$)" obrigatorio erro={errors.itens?.[i]?.valor}>
              <Controller control={control} name={`itens.${i}.valor`}
                rules={{ validate: (v) => paraCentavos(v) !== null || 'valor inválido' }}
                render={({ field: { ref: _ref, ...campo } }) => <CampoDinheiro {...campo} />} />
            </Campo>
            <div style={{ paddingTop: '1.6rem' }}>
              <button type="button" className="btn-perigo btn-pequeno" aria-label="Remover serviço" title={fields.length === 1 ? 'A OS precisa ter pelo menos um serviço' : 'Remover'}
                disabled={fields.length === 1} onClick={() => remove(i)}>✕</button>
            </div>
          </div>
        ))}
        <button type="button" className="btn-sec" onClick={() => append({ ...linhaVazia })}>+ Adicionar serviço</button>
        <div className="total-os">Total: {reais(totalCentavos)}</div>
      </section>

      <section className="cartao">
        <Campo rotulo="Observações da OS" erro={errors.observacoes}><textarea rows={3} {...register('observacoes')} /></Campo>
      </section>

      <div className="acoes">
        <button className="btn-primario" disabled={isSubmitting || (editando && os?.paga)}>{isSubmitting ? 'Salvando…' : 'Salvar OS'}</button>
        <button type="button" className="btn-sec" onClick={() => nav(-1)}>Cancelar</button>
      </div>
    </form>
  );
}
