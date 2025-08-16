import os
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel
from langchain_google_genai import ChatGoogleGenerativeAI

API_KEY = os.getenv("BACKEND_API_KEY", "")
GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-1.5-flash")

app = FastAPI(title="WP Auto Summary Backend (FastAPI)")

class Inp(BaseModel):
    title: str
    text: str
    language: str = "id_ID"
    length: int = 100

@app.post("/summarize")
def summarize(inp: Inp, authorization: str | None = Header(default=None)):
    if API_KEY:
        if not authorization or not authorization.startswith("Bearer "):
            raise HTTPException(status_code=401, detail="Unauthorized")
        token = authorization.split(" ", 1)[1]
        if token != API_KEY:
            raise HTTPException(status_code=401, detail="Unauthorized")

    llm = ChatGoogleGenerativeAI(model=GEMINI_MODEL, temperature=0.2)
    prompt = (
        f"Ringkas berita berikut dalam bahasa Indonesia, sekitar {inp.length} kata, "
        "gaya newsroom (faktual, padat, tanpa clickbait), pertahankan angka/tanggal penting.\n\n"
        f"Judul: {inp.title}\n\nTeks:\n{inp.text}"
    )
    out = llm.invoke(prompt).content.strip()
    return {"summary": out, "model": GEMINI_MODEL}
