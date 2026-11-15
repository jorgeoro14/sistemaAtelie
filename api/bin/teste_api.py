#!/usr/bin/env python3
"""Teste de ponta a ponta da API (precisa do servidor em localhost:8000 e do seed.sql).
Uso: python3 api/bin/teste_api.py [base_url]
Cria dados de teste no banco de DESENVOLVIMENTO. Não rode em produção."""
import json
import sys
import urllib.request
import urllib.error
import http.cookiejar
from datetime import date, timedelta

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8000") + "/api"
jar = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
csrf = ""
falhas = 0
total = 0


def chamar(metodo, rota, corpo=None, token=True):
    dados = json.dumps(corpo).encode() if corpo is not None else None
    req = urllib.request.Request(BASE + rota, data=dados, method=metodo)
    req.add_header("Content-Type", "application/json")
    if token and csrf:
        req.add_header("X-CSRF-Token", csrf)
    try:
        r = op.open(req)
        txt = r.read().decode()
        return r.status, (json.loads(txt) if txt else None)
    except urllib.error.HTTPError as e:
        txt = e.read().decode()
        try:
            return e.code, json.loads(txt)
        except Exception:
            return e.code, txt


def checar(nome, obtido, esperado):
    global falhas, total
    total += 1
    ok = obtido == esperado
    if not ok:
        falhas += 1
    print(("OK   " if ok else "FALHA"), nome, "" if ok else f"(esperado {esperado!r}, obtido {obtido!r})")


def corpo(resp):
    return resp[1]


hoje = date.today()
d = lambda n: (hoje + timedelta(days=n)).isoformat()

# ---------- autenticação ----------
checar("GET /me sem login = 401", chamar("GET", "/me")[0], 401)
checar("login errado = 401", chamar("POST", "/login", {"usuario": "admin", "senha": "x"}, False)[0], 401)
s, b = chamar("POST", "/login", {"usuario": "admin", "senha": "1234"}, False)
checar("login admin = 200", s, 200)
csrf = b["csrf_token"]
checar("trocar_senha obrigatório", b["trocar_senha"], True)
checar("POST sem CSRF = 403", chamar("POST", "/clientes", {"nome": "x"}, False)[0], 403)

s, b = chamar("POST", "/me/senha", {"senha_atual": "1234", "nova_senha": "senha-forte-1", "confirmacao": "senha-forte-1"})
checar("trocar senha = 200/204", s in (200, 204), True)
s, b = chamar("GET", "/me")
checar("trocar_senha zerado", b["trocar_senha"], False)

# ---------- clientes ----------
s, b = chamar("POST", "/clientes", {"nome": "", "telefone": "123"})
checar("cliente inválido = 422", s, 422)
s, b = chamar("POST", "/clientes", {"nome": "Maria Teste", "telefone": "(45) 99999-1234", "observacoes": "teste"})
checar("cliente criado = 201", s, 201)
cli = b["id"]
checar("telefone só dígitos", b["telefone"], "45999991234")
s, b = chamar("POST", "/clientes", {"nome": "Maria Outra", "telefone": "45999991234"})
checar("telefone repetido cria com aviso", (s, bool(b.get("avisos"))), (201, True))
cli2 = b["id"]
s, b = chamar("GET", "/clientes?q=Maria")
checar("busca clientes", s == 200 and b["paginacao"]["total"] >= 2, True)
s, b = chamar("PUT", f"/clientes/{cli}", {"nome": "Maria Teste Silva", "telefone": "45999991234", "observacoes": "x", "ativo": True})
checar("editar cliente = 200", s, 200)
s, b = chamar("DELETE", f"/clientes/{cli2}")
checar("excluir cliente sem OS = 200", (s, (b or {}).get("acao")), (200, "excluido"))

# ---------- costureiras e serviços ----------
s, b = chamar("POST", "/costureiras", {"nome": "Ana Costureira", "telefone": "45988887777"})
checar("costureira criada = 201", s, 201)
cost = b["id"]
s, b = chamar("GET", "/servicos")
checar("serviços do seed", s == 200 and len(b["dados"]) >= 6, True)
sv = b["dados"][0]["id"]
s, b = chamar("POST", "/servicos", {"nome": "Serviço Teste", "categoria": "ajuste", "valor_padrao_centavos": 2500})
checar("serviço criado = 201", s, 201)
sv2 = b["id"]
s, b = chamar("PUT", f"/servicos/{sv2}", {"nome": "Serviço Teste 2", "categoria": "ajuste", "valor_padrao_centavos": 3000, "ativo": True})
checar("serviço editado = 200", s, 200)

# ---------- ordens de serviço ----------
os_ok = {
    "cliente_id": cli, "costureira_id": cost, "prioridade": "alta",
    "data_recebimento": d(0), "data_prevista": d(5), "observacoes": "teste",
    "itens": [
        {"servico_id": sv, "descricao": "Barra", "valor_centavos": 3500},
        {"servico_id": sv2, "descricao": "Cintura", "valor_centavos": 6000},
    ],
}
s, b = chamar("POST", "/os", {**os_ok, "data_prevista": d(-3)})
checar("prevista < recebimento = 422", s, 422)
s, b = chamar("POST", "/os", {**os_ok, "itens": []})
checar("OS sem itens = 422", s, 422)
s, b = chamar("POST", "/os", os_ok)
checar("OS criada = 201", s, 201)
osid = b["id"]
checar("total = soma dos itens", b["total_centavos"], 9500)
checar("status inicial recebido", b["status"], "recebido")
n1 = b["numero"]
s, b = chamar("POST", "/os", {**os_ok, "prioridade": True})
checar("prioridade booleana aceita", (s, b.get("prioridade")), (201, "alta"))
os2 = b["id"]
checar("número sequencial", b["numero"], n1 + 1)

s, b = chamar("GET", f"/os/{osid}")
checar("detalhe OS com 2 itens", (s, len(b["itens"])), (200, 2))
s, b = chamar("GET", "/os?abertas=1")
checar("lista OS abertas", s == 200 and b["paginacao"]["total"] >= 2, True)
s, b = chamar("GET", "/os?q=Maria&status=recebido&prioridade=alta")
checar("filtros combinados", s, 200)

s, b = chamar("PATCH", f"/os/{osid}/status", {"status": "entregue"})
checar("entregue sem estar pronta = 422", (s, b.get("erro")), (422, "precisa_estar_pronta"))
s, b = chamar("PATCH", f"/os/{osid}/status", {"status": "em_andamento"})
checar("em_andamento = 200", s, 200)
s, b = chamar("PATCH", f"/os/{osid}/status", {"status": "pronto"})
checar("pronto = 200", (s, b["data_pronto"] is not None), (200, True))
s, b = chamar("POST", f"/os/{osid}/pagamento", {"forma": "pix", "data_pagamento": d(0)})
checar("pagamento = 200/201", s in (200, 201), True)
s, b = chamar("POST", f"/os/{osid}/pagamento", {"forma": "pix", "data_pagamento": d(0)})
checar("pagar de novo = ja_paga", (s, b.get("erro")), (422, "ja_paga"))
s, b = chamar("PUT", f"/os/{osid}", os_ok)
checar("editar OS paga = os_paga", (s, b.get("erro")), (422, "os_paga"))
s, b = chamar("PATCH", f"/os/{osid}/status", {"status": "cancelado"})
checar("cancelar OS paga = bloqueado", (s, b.get("erro")), (422, "os_paga"))
s, b = chamar("PATCH", f"/os/{osid}/status", {"status": "entregue"})
checar("entregue = 200", (s, b["data_entrega"] is not None), (200, True))
s, b = chamar("DELETE", f"/os/{osid}/pagamento")
checar("estornar = 200/204", s in (200, 204), True)
s, b = chamar("PUT", f"/os/{osid}", {**os_ok, "itens": [{"servico_id": sv, "descricao": "Barra", "valor_centavos": 4000}]})
checar("editar OS após estorno = 200", (s, b["total_centavos"]), (200, 4000))
s, b = chamar("PATCH", f"/os/{os2}/status", {"status": "cancelado"})
checar("cancelar OS = 200", s, 200)
s, b = chamar("PATCH", f"/os/{os2}/status", {"status": "recebido"})
checar("reabrir OS cancelada = 200", s, 200)

s, b = chamar("DELETE", f"/clientes/{cli}")
checar("cliente com OS: inativa", (s, (b or {}).get("acao")), (200, "inativado"))
s, b = chamar("PUT", f"/clientes/{cli}", {"nome": "Maria Teste Silva", "telefone": "45999991234", "ativo": True})

# ---------- agenda ----------
s, b = chamar("POST", "/agendamentos", {"cliente_id": cli, "os_id": os2, "data_hora": d(2) + " 14:30", "tipo": "prova", "observacao": "prova"})
checar("compromisso criado = 201", s, 201)
ag = b["id"]
s, b = chamar("GET", f"/agenda?de={d(0)}&ate={d(10)}")
checar("agenda unificada", s == 200 and len(b) >= 1, True)
s, b = chamar("PUT", f"/agendamentos/{ag}", {"cliente_id": cli, "os_id": os2, "data_hora": d(3) + " 10:00", "tipo": "medidas", "observacao": "x", "status": "marcado"})
checar("editar compromisso = 200", s, 200)
s, b = chamar("DELETE", f"/agendamentos/{ag}")
checar("cancelar compromisso = 200/204", s in (200, 204), True)

# ---------- dashboard e relatórios ----------
s, b = chamar("GET", "/dashboard")
checar("dashboard = 200", s, 200)
for r in ("resumo", "costureiras", "clientes"):
    s, b = chamar("GET", f"/relatorios/{r}?de={d(-30)}&ate={d(30)}")
    checar(f"relatório {r} = 200", s, 200)
s, b = chamar("GET", f"/relatorios/faturamento-mensal?ano={hoje.year}")
checar("relatório mensal = 200", s, 200)

# ---------- usuários ----------
s, b = chamar("GET", "/usuarios")
checar("listar usuários = 200", s, 200)
s, b = chamar("POST", "/usuarios", {"nome": "Helper", "usuario": "helper", "senha": "outra-senha-1"})
checar("criar usuário = 201", s, 201)
uid = b["id"]
s, b = chamar("POST", "/usuarios", {"nome": "Helper", "usuario": "HELPER", "senha": "outra-senha-1"})
checar("usuário duplicado (case) = 422", s, 422)
s, b = chamar("POST", f"/usuarios/{uid}/redefinir-senha", {"nova_senha": "terceira-senha-1"})
checar("redefinir senha = 200/204", s in (200, 204), True)

# ---------- logout ----------
chamar("POST", "/logout")
checar("após logout /me = 401", chamar("GET", "/me")[0], 401)

# ---------- bloqueio por tentativas ----------
for _ in range(5):
    chamar("POST", "/login", {"usuario": "helper", "senha": "errada"}, False)
s, b = chamar("POST", "/login", {"usuario": "helper", "senha": "terceira-senha-1"}, False)
checar("6ª tentativa: usuário bloqueado = 403", s, 403)

print(f"\n{total - falhas}/{total} verificações passaram")
sys.exit(1 if falhas else 0)
