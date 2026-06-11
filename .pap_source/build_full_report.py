from __future__ import annotations

import math
import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont
from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
FIG_DIR = ROOT / ".pap_source" / "report_assets" / "figures"
OUT = Path(os.environ.get("PAP_REPORT_OUT", ROOT / "docs" / "PAP_ENTREGA" / "Relatorio_PAP_Greenerry_Srijan_Gautam.docx"))
REAL_FIGURES = {
    "fig_arquitetura_greenerry.png",
    "fig_modelo_dados_simplificado.png",
    "fig_fluxo_compra_conteudo.png",
}
SCHOOL_LOGO = ROOT / ".pap_source" / "report_assets" / "school_logo_crop.png"
REPUBLICA_LOGO = ROOT / ".pap_source" / "report_assets" / "republica_logo_crop.png"
BRAND_WORDMARK = ROOT / "assets" / "img" / "brand" / "greenerry-wordmark-dark.png"


def font_path(name: str = "arial.ttf") -> str:
    candidates = [
        Path("C:/Windows/Fonts") / name,
        Path("C:/Windows/Fonts/arial.ttf"),
        Path("C:/Windows/Fonts/calibri.ttf"),
    ]
    for candidate in candidates:
        if candidate.exists():
            return str(candidate)
    return ""


def load_font(size: int, bold: bool = False):
    name = "arialbd.ttf" if bold else "arial.ttf"
    path = font_path(name)
    if path:
        return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def wrapped_lines(draw: ImageDraw.ImageDraw, text: str, font, max_width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words:
        test = f"{current} {word}".strip()
        if draw.textbbox((0, 0), test, font=font)[2] <= max_width:
            current = test
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines


def draw_centered_text(draw, box, text, font, fill=(26, 32, 44), max_lines=4):
    x1, y1, x2, y2 = box
    lines = wrapped_lines(draw, text, font, int(x2 - x1 - 30))[:max_lines]
    line_h = font.size + 6
    total_h = line_h * len(lines)
    y = y1 + ((y2 - y1) - total_h) / 2
    for line in lines:
        bbox = draw.textbbox((0, 0), line, font=font)
        x = x1 + ((x2 - x1) - (bbox[2] - bbox[0])) / 2
        draw.text((x, y), line, font=font, fill=fill)
        y += line_h


def arrow(draw, start, end, fill=(85, 99, 119), width=4):
    draw.line([start, end], fill=fill, width=width)
    ang = math.atan2(end[1] - start[1], end[0] - start[0])
    size = 14
    points = [
        end,
        (end[0] - size * math.cos(ang - math.pi / 6), end[1] - size * math.sin(ang - math.pi / 6)),
        (end[0] - size * math.cos(ang + math.pi / 6), end[1] - size * math.sin(ang + math.pi / 6)),
    ]
    draw.polygon(points, fill=fill)


def make_architecture_diagram() -> Path:
    path = FIG_DIR / "fig_arquitetura_greenerry.png"
    img = Image.new("RGB", (1600, 950), "#f7f9fc")
    draw = ImageDraw.Draw(img)
    title = load_font(42, True)
    h = load_font(28, True)
    body = load_font(22)
    small = load_font(18)

    draw.text((70, 48), "Arquitetura geral da plataforma Greenerry", font=title, fill="#102033")

    boxes = {
        "visitante": (80, 170, 360, 300),
        "cliente": (80, 360, 360, 490),
        "artista": (80, 550, 360, 680),
        "admin": (80, 740, 360, 870),
        "frontend": (560, 250, 960, 430),
        "backend": (560, 560, 960, 740),
        "db": (1180, 250, 1510, 430),
        "servicos": (1180, 560, 1510, 740),
    }
    fills = {
        "visitante": "#e9f2ff",
        "cliente": "#e9f7ef",
        "artista": "#fff4df",
        "admin": "#f4eafe",
        "frontend": "#ffffff",
        "backend": "#ffffff",
        "db": "#eef4ff",
        "servicos": "#f7f1e8",
    }
    labels = {
        "visitante": "Visitante\nconsulta músicas, artistas e loja",
        "cliente": "Cliente\nfavoritos, playlists, compras e perfil",
        "artista": "Artista\nlançamentos, produtos e estatísticas",
        "admin": "Administração\nmoderação, relatórios e configurações",
        "frontend": "Interface Web\nHTML, CSS, JavaScript, páginas responsivas",
        "backend": "Aplicação PHP\nsessões, permissões, validação, APIs e regras",
        "db": "Base de dados MySQL\nclientes, música, géneros, produtos, encomendas",
        "servicos": "Serviços de apoio\ne-mails, faturas PDF, uploads e exportações",
    }
    for key, box in boxes.items():
        draw.rounded_rectangle(box, radius=22, fill=fills[key], outline="#ccd6e2", width=3)
        lines = labels[key].split("\n")
        draw_centered_text(draw, (box[0], box[1] + 12, box[2], box[1] + 58), lines[0], h)
        draw_centered_text(draw, (box[0] + 18, box[1] + 66, box[2] - 18, box[3] - 12), lines[1], body)

    for key in ["visitante", "cliente", "artista", "admin"]:
        x1, y1, x2, y2 = boxes[key]
        arrow(draw, (x2, (y1 + y2) // 2), (boxes["frontend"][0], (boxes["frontend"][1] + boxes["frontend"][3]) // 2))
    arrow(draw, (760, 430), (760, 560))
    arrow(draw, (960, 340), (1180, 340))
    arrow(draw, (960, 650), (1180, 650))
    arrow(draw, (1345, 430), (1345, 560))

    draw.text((560, 810), "Separação por áreas: páginas públicas, área de utilizador, área de artista e painel administrativo.", font=small, fill="#4a5568")
    img.save(path, quality=95)
    return path


def make_database_diagram() -> Path:
    path = FIG_DIR / "fig_modelo_dados_simplificado.png"
    img = Image.new("RGB", (1900, 1360), "#f8fafc")
    draw = ImageDraw.Draw(img)
    title = load_font(44, True)
    h = load_font(25, True)
    body = load_font(18)
    small = load_font(15)
    draw.text((70, 45), "Modelo de dados da Greenerry por módulos", font=title, fill="#0f172a")
    draw.text((72, 105), "Representação resumida das tabelas principais e das ligações mais importantes usadas no website.", font=body, fill="#475467")

    modules = [
        {
            "title": "Contas e segurança",
            "color": "#dbeafe",
            "x": 70,
            "y": 180,
            "tables": [
                ("cliente", "idCliente PK\ne-mail único\nestado, foto, banner, bio"),
                ("admin", "idAdmin PK\ncargo, ativo\npermissões de backoffice"),
                ("verificacao_email", "idCliente FK\nhash/código\nexpiração e usado_em"),
                ("recuperacao_password", "idCliente FK\ncódigo temporário\nnova palavra-passe"),
            ],
        },
        {
            "title": "Música, géneros e biblioteca",
            "color": "#dcfce7",
            "x": 520,
            "y": 180,
            "tables": [
                ("genero", "idGenero PK\nnome, slug, estado"),
                ("release_musical", "idCliente FK\nidGenero FK\ntipo, data, estado"),
                ("faixa", "idRelease FK\nidGenero FK\náudio, duração, estado"),
                ("faixa_listen", "idFaixa FK\nidCliente FK\nsegundos_ouvidos"),
                ("playlist / playlist_faixa", "idCliente FK\nidFaixa FK\nordem das músicas"),
                ("favorito_musica", "idCliente FK\nidFaixa FK\nbiblioteca pessoal"),
                ("seguir_artista", "idSeguidor FK\nidArtista FK\nseguimento"),
            ],
        },
        {
            "title": "Loja, stock e encomendas",
            "color": "#fef3c7",
            "x": 970,
            "y": 180,
            "tables": [
                ("categoria", "idCategoria PK\nnome, tamanhos, estado"),
                ("produto", "idCliente FK\nidCategoria FK\npreço, IVA, stock, estado"),
                ("produto_imagem", "idProduto FK\nficheiro, ordem"),
                ("produto_tamanho_stock", "idProduto FK\nidTamanho FK\nstock por tamanho"),
                ("encomenda", "idCliente FK\ntotais, pagamento, envio"),
                ("encomenda_item", "idEncomenda FK\nidProduto FK\npreço histórico, comissão"),
                ("pagamento", "idEncomenda FK\nvalor, método, referência"),
                ("produto_review", "idProduto FK\nidCliente FK\nrating e comentário"),
            ],
        },
        {
            "title": "Comunicação e administração",
            "color": "#ede9fe",
            "x": 1420,
            "y": 180,
            "tables": [
                ("mensagem_admin", "idCliente FK\nassunto, resposta\nestado"),
                ("encomenda_mensagem", "idEncomenda FK\ncomprador/artista\nmensagens por compra"),
                ("notificacao", "idCliente FK\ntipo, lida, criado_em"),
                ("configuracao_site", "chave PK\nvalor\nopções da plataforma"),
                ("page_maintenance", "página\nestado de manutenção"),
            ],
        },
    ]

    module_boxes = {}
    table_centers = {}
    table_boxes = {}
    for module in modules:
        x = module["x"]
        y = module["y"]
        w = 390
        hgt = 1020
        draw.rounded_rectangle((x, y, x + w, y + hgt), radius=24, fill="#ffffff", outline="#cbd5e1", width=3)
        draw.rounded_rectangle((x, y, x + w, y + 62), radius=24, fill=module["color"], outline="#cbd5e1", width=2)
        draw.text((x + 24, y + 18), module["title"], font=h, fill="#102033")
        module_boxes[module["title"]] = (x, y, x + w, y + hgt)
        ty = y + 86
        for name, desc in module["tables"]:
            box = (x + 24, ty, x + w - 24, ty + 86)
            draw.rounded_rectangle(box, radius=14, fill="#f8fafc", outline="#d0d7e2", width=2)
            draw.text((box[0] + 16, box[1] + 12), name, font=small, fill="#0f172a")
            for idx, line in enumerate(desc.split("\n")):
                draw.text((box[0] + 16, box[1] + 36 + idx * 16), line, font=small, fill="#475467")
            key = name.split(" / ")[0]
            table_centers[key] = ((box[0] + box[2]) // 2, (box[1] + box[3]) // 2)
            table_boxes[key] = box
            if "playlist_faixa" in name:
                table_centers["playlist_faixa"] = table_centers["playlist"]
                table_boxes["playlist_faixa"] = box
            ty += 104

    relation_color = "#334155"
    module_links = [
        ("Contas e segurança", "Música, géneros e biblioteca", "cliente -> artista, biblioteca e audições"),
        ("Música, géneros e biblioteca", "Loja, stock e encomendas", "artista -> produtos e merchandising"),
        ("Loja, stock e encomendas", "Comunicação e administração", "encomenda -> mensagens, notificações e controlo"),
    ]

    summary_y = 1220
    draw.rounded_rectangle((70, summary_y, 1830, summary_y + 112), radius=18, fill="#ffffff", outline="#cbd5e1", width=2)
    draw.text((95, summary_y + 16), "Leitura do modelo:", font=body, fill="#0f172a")
    draw.text((300, summary_y + 17), "PK = chave primária | FK = chave estrangeira | as relações detalhadas estão documentadas no DER e no MER/FNN anexados.", font=small, fill="#475467")
    draw.text((95, summary_y + 52), "Relações principais:", font=body, fill="#0f172a")
    relation_text = " | ".join(label for _left, _right, label in module_links)
    for idx, line in enumerate(wrapped_lines(draw, relation_text, small, 1400)[:2]):
        draw.text((330, summary_y + 53 + idx * 18), line, font=small, fill="#475467")
    draw.text((95, summary_y + 88), "Esta figura resume a base de dados por módulos para evitar linhas cruzadas no relatório final.", font=small, fill="#475467")
    img.save(path, quality=95)
    return path


def make_flow_diagram() -> Path:
    path = FIG_DIR / "fig_fluxo_compra_conteudo.png"
    img = Image.new("RGB", (1600, 850), "#f8fafc")
    draw = ImageDraw.Draw(img)
    title = load_font(42, True)
    h = load_font(23, True)
    body = load_font(18)
    draw.text((70, 50), "Fluxos principais: compra, música e moderação", font=title, fill="#0f172a")
    rows = [
        ("Cliente", ["Regista conta", "Confirma código por e-mail", "Ouve músicas", "Compra produto", "Consulta fatura"]),
        ("Artista", ["Envia música", "Cria produto", "Acompanha encomendas", "Consulta análises"]),
        ("Admin", ["Revê conteúdos", "Aprova/rejeita", "Gere géneros", "Responde mensagens", "Exporta relatórios"]),
    ]
    colors = ["#e0f2fe", "#fef3c7", "#ede9fe"]
    y = 150
    for r, (label, steps) in enumerate(rows):
        draw.text((90, y + 40), label, font=h, fill="#111827")
        x = 280
        previous = None
        for i, step in enumerate(steps):
            box = (x, y, x + 220, y + 110)
            draw.rounded_rectangle(box, radius=18, fill=colors[r], outline="#cbd5e1", width=2)
            draw_centered_text(draw, box, step, body, fill="#1f2937")
            if previous:
                arrow(draw, (previous[2], y + 55), (box[0], y + 55), fill="#64748b", width=3)
            previous = box
            x += 250
        y += 210
    img.save(path, quality=95)
    return path


def set_cell_text(cell, text: str, bold: bool = False, size: int = 10):
    cell.text = ""
    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    r = p.add_run(text)
    r.bold = bold
    r.font.name = "Times New Roman"
    r.font.size = Pt(size)


def shade_cell(cell, color: str):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:fill"), color)
    tc_pr.append(shd)


def set_cell_margins(cell, top=100, start=120, bottom=100, end=120):
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for m, v in [("top", top), ("start", start), ("bottom", bottom), ("end", end)]:
        node = tc_mar.find(qn(f"w:{m}"))
        if node is None:
            node = OxmlElement(f"w:{m}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(v))
        node.set(qn("w:type"), "dxa")


def set_repeat_table_header(row):
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def add_table(doc: Document, headers: list[str], data: list[list[str]], widths: list[float] | None = None):
    table = doc.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    hdr = table.rows[0]
    set_repeat_table_header(hdr)
    for i, header in enumerate(headers):
        set_cell_text(hdr.cells[i], header, bold=True, size=10)
        shade_cell(hdr.cells[i], "E8EEF5")
        hdr.cells[i].vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        set_cell_margins(hdr.cells[i])
    for row in data:
        cells = table.add_row().cells
        for i, value in enumerate(row):
            set_cell_text(cells[i], value, size=10)
            cells[i].vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            set_cell_margins(cells[i])
    if widths:
        for row in table.rows:
            for idx, width in enumerate(widths):
                row.cells[idx].width = Cm(width)
    return table


def add_field(paragraph, instruction: str, placeholder: str = ""):
    run = paragraph.add_run()
    fld_begin = OxmlElement("w:fldChar")
    fld_begin.set(qn("w:fldCharType"), "begin")
    run._r.append(fld_begin)

    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = instruction
    run._r.append(instr)

    fld_separate = OxmlElement("w:fldChar")
    fld_separate.set(qn("w:fldCharType"), "separate")
    run._r.append(fld_separate)

    if placeholder:
        paragraph.add_run(placeholder)

    run_end = paragraph.add_run()
    fld_end = OxmlElement("w:fldChar")
    fld_end.set(qn("w:fldCharType"), "end")
    run_end._r.append(fld_end)


def add_page_number(paragraph):
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run("Página ")
    run.font.name = "Times New Roman"
    run.font.size = Pt(9)
    add_field(paragraph, "PAGE")


def set_update_fields(doc: Document):
    settings = doc.settings.element
    update_fields = settings.find(qn("w:updateFields"))
    if update_fields is None:
        update_fields = OxmlElement("w:updateFields")
        settings.append(update_fields)
    update_fields.set(qn("w:val"), "true")


def set_styles(doc: Document):
    sec = doc.sections[0]
    sec.page_width = Cm(21)
    sec.page_height = Cm(29.7)
    sec.top_margin = Cm(3)
    sec.bottom_margin = Cm(2.5)
    sec.left_margin = Cm(3)
    sec.right_margin = Cm(2)
    sec.header_distance = Cm(1.25)
    sec.footer_distance = Cm(1.25)

    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Times New Roman"
    normal.font.size = Pt(12)
    normal.paragraph_format.line_spacing = 1.5
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.first_line_indent = Cm(0.75)

    for name, size, before, after in [
        ("Heading 1", 16, 18, 8),
        ("Heading 2", 14, 12, 6),
        ("Heading 3", 12, 8, 4),
    ]:
        style = styles[name]
        style.font.name = "Times New Roman"
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = RGBColor(31, 49, 68)
        style.paragraph_format.space_before = Pt(before)
        style.paragraph_format.space_after = Pt(after)
        style.paragraph_format.keep_with_next = True

    for name in ["List Bullet", "List Number"]:
        style = styles[name]
        style.font.name = "Times New Roman"
        style.font.size = Pt(12)
        style.paragraph_format.line_spacing = 1.5
        style.paragraph_format.space_after = Pt(3)


def p(doc: Document, text: str = "", style: str | None = None, align=None, first_line: bool = True):
    para = doc.add_paragraph(style=style)
    if text:
        run = para.add_run(text)
        run.font.name = "Times New Roman"
        if style is None:
            run.font.size = Pt(12)
    if align is not None:
        para.alignment = align
    elif style is None:
        para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    if not first_line:
        para.paragraph_format.first_line_indent = Cm(0)
    return para


def bullet(doc: Document, text: str):
    para = p(doc, text, style="List Bullet", first_line=False)
    para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    return para


def number(doc: Document, text: str):
    para = p(doc, text, style="List Number", first_line=False)
    para.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    return para


def heading(doc: Document, text: str, level: int = 1):
    return p(doc, text, style=f"Heading {level}", first_line=False)


def caption(doc: Document, text: str):
    para = p(doc, text, first_line=False, align=WD_ALIGN_PARAGRAPH.CENTER)
    for run in para.runs:
        run.italic = True
        run.font.size = Pt(10)
        run.font.color.rgb = RGBColor(80, 80, 80)
    return para


def screenshot_placeholder(doc: Document, caption_text: str):
    label = caption_text.split(" - ", 1)[1] if " - " in caption_text else caption_text
    label = label.rstrip(".")
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    cell = table.rows[0].cells[0]
    shade_cell(cell, "F8FAFC")
    set_cell_margins(cell, top=360, start=240, bottom=360, end=240)
    set_cell_text(cell, f"Espaço reservado para captura final:\n{label}", bold=False, size=11)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    for para in cell.paragraphs:
        para.alignment = WD_ALIGN_PARAGRAPH.CENTER


def add_figure(doc: Document, image: Path, caption_text: str, width_cm: float = 15.5):
    if image.name not in REAL_FIGURES:
        screenshot_placeholder(doc, caption_text)
        caption(doc, caption_text)
        return
    if not image.exists():
        return
    para = doc.add_paragraph()
    para.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = para.add_run()
    run.add_picture(str(image), width=Cm(width_cm))
    caption(doc, caption_text)


def cover(doc: Document):
    section = doc.sections[0]
    section.page_width = Cm(21)
    section.page_height = Cm(29.7)
    section.top_margin = Cm(1.8)
    section.bottom_margin = Cm(1.8)
    section.left_margin = Cm(2.2)
    section.right_margin = Cm(2.2)

    logo_row = doc.add_table(rows=1, cols=2)
    logo_row.alignment = WD_TABLE_ALIGNMENT.CENTER
    logo_row.columns[0].width = Cm(8)
    logo_row.columns[1].width = Cm(8)
    left = logo_row.rows[0].cells[0]
    right = logo_row.rows[0].cells[1]
    left.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    right.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    if SCHOOL_LOGO.exists():
        r = left.paragraphs[0].add_run()
        r.add_picture(str(SCHOOL_LOGO), width=Cm(4.7))
    if REPUBLICA_LOGO.exists():
        right.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.RIGHT
        r = right.paragraphs[0].add_run()
        r.add_picture(str(REPUBLICA_LOGO), width=Cm(4.2))

    for _ in range(2):
        p(doc, "", first_line=False)
    school = p(doc, "Escola Secundária Cacilhas-Tejo", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
    school.runs[0].bold = True
    school.runs[0].font.size = Pt(14)
    p(doc, "Curso Profissional de Técnico de Gestão e Programação de Sistemas Informáticos", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
    p(doc, "12.º K", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)

    for _ in range(2):
        p(doc, "", first_line=False)
    title = p(doc, "PROVA DE APTIDÃO PROFISSIONAL", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
    title.runs[0].bold = True
    title.runs[0].font.size = Pt(17)
    title.runs[0].font.color.rgb = RGBColor(15, 23, 42)
    report = p(doc, "RELATÓRIO FINAL", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
    report.runs[0].bold = True
    report.runs[0].font.size = Pt(26)
    report.runs[0].font.color.rgb = RGBColor(17, 24, 39)

    if BRAND_WORDMARK.exists():
        logo = doc.add_paragraph()
        logo.alignment = WD_ALIGN_PARAGRAPH.CENTER
        logo.add_run().add_picture(str(BRAND_WORDMARK), width=Cm(11.8))
    else:
        main = p(doc, "GREENERRY", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
        main.runs[0].bold = True
        main.runs[0].font.size = Pt(24)
    subtitle = p(doc, "Plataforma Web de Música, Artistas e Merchandising", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
    subtitle.runs[0].font.size = Pt(13)
    subtitle.runs[0].font.color.rgb = RGBColor(71, 85, 105)

    for _ in range(3):
        p(doc, "", first_line=False)
    add_table(doc, ["Elemento", "Informação"], [
        ["Aluno", "Srijan Gautam"],
        ["Orientadora", "Elisabete Vaz"],
        ["Ano letivo", "2025/2026"],
    ], [4.2, 8.2])
    for _ in range(2):
        p(doc, "", first_line=False)
    place = p(doc, "Almada, junho de 2026", align=WD_ALIGN_PARAGRAPH.CENTER, first_line=False)
    place.runs[0].font.size = Pt(11)
    place.runs[0].font.color.rgb = RGBColor(71, 85, 105)


def build():
    FIG_DIR.mkdir(parents=True, exist_ok=True)
    arch = make_architecture_diagram()
    db_diag = make_database_diagram()
    flow = make_flow_diagram()

    doc = Document()
    set_styles(doc)
    set_update_fields(doc)
    cover(doc)

    section = doc.add_section(WD_SECTION.NEW_PAGE)
    section.page_width = Cm(21)
    section.page_height = Cm(29.7)
    section.top_margin = Cm(3)
    section.bottom_margin = Cm(2.5)
    section.left_margin = Cm(3)
    section.right_margin = Cm(2)
    section.footer.is_linked_to_previous = False
    footer = section.footer.paragraphs[0]
    add_page_number(footer)

    heading(doc, "Resumo", 1)
    p(doc, "O presente relatório descreve o desenvolvimento da Greenerry, uma plataforma Web criada no âmbito da Prova de Aptidão Profissional do Curso Profissional de Técnico de Gestão e Programação de Sistemas Informáticos. A aplicação junta descoberta musical, perfis de artistas, loja de merchandising, área de utilizador, área de artista e painel administrativo. O projeto foi desenvolvido com PHP, MySQL/MariaDB, HTML, CSS e JavaScript, em ambiente local com XAMPP, tendo como objetivo demonstrar uma solução completa, funcional e ligada a uma base de dados real.")
    p(doc, "A Greenerry permite que visitantes consultem músicas, artistas e produtos; que utilizadores registados criem favoritos, playlists, sigam artistas e realizem compras; que artistas acompanhem conteúdos, vendas e estatísticas; e que administradores façam gestão de utilizadores, géneros, lançamentos, produtos, encomendas, mensagens, relatórios e definições. O relatório apresenta a fundamentação da escolha, os objetivos, a metodologia, as tecnologias utilizadas, a estrutura da base de dados, o desenvolvimento, os testes realizados, as dificuldades sentidas e as melhorias futuras.")
    p(doc, "Palavras-chave: Greenerry; música; merchandising; PHP; MySQL; PAP; plataforma Web.", first_line=False)

    heading(doc, "Índice", 1)
    toc = p(doc, first_line=False)
    add_field(toc, 'TOC \\o "1-3" \\h \\z \\u', "Índice automático")

    heading(doc, "Índice de figuras", 1)
    for item in [
        "Figura 1 - Página inicial da Greenerry.",
        "Figura 2 - Arquitetura geral da plataforma.",
        "Figura 3 - Modelo de dados por módulos.",
        "Figura 4 - Fluxos principais do sistema.",
        "Figura 5 - Catálogo de música.",
        "Figura 6 - Listagem de artistas.",
        "Figura 7 - Loja online.",
        "Figura 8 - Sidebars esquerda e direita com leitor ativo.",
        "Figura 9 - Página de detalhe de produto.",
        "Figura 10 - Carrinho com produto selecionado.",
        "Figura 11 - Checkout/finalização de compra.",
        "Figura 12 - Dashboard de artista.",
        "Figura 13 - Produtos na área de artista.",
        "Figura 14 - Análises de artista.",
        "Figura 15 - Gestão administrativa de géneros.",
        "Figura 16 - Painel administrativo com dados de demonstração.",
        "Figura 17 - Relatório financeiro administrativo.",
        "Figura 18 - Revisão administrativa de produtos.",
        "Figura 19 - Gestão administrativa de encomendas.",
        "Figura 20 - Repositório GitHub e histórico do projeto.",
        "Figura 21 - Tabela administrativa de produtos com imagens.",
        "Figura 22 - Login público sem entrada direta de administração.",
        "Figura 23 - Login reservado da administração.",
        "Figura 24 - Menu do perfil do utilizador autenticado.",
        "Figura 25 - Perfil do utilizador autenticado.",
        "Figura 26 - Biblioteca com playlists, favoritos e artistas.",
        "Figura 27 - Notificações do utilizador.",
        "Figura 28 - Contacto com a administração.",
        "Figura 29 - Upload de música na área de artista.",
        "Figura 30 - Upload de produto na área de artista.",
        "Figura 31 - Curadoria da homepage no painel administrativo.",
        "Figura 32 - Gestão administrativa de categorias.",
        "Figura 33 - Revisão administrativa de lançamentos.",
        "Figura 34 - Gestão administrativa de utilizadores.",
        "Figura 35 - Mensagens no painel administrativo.",
        "Figura 36 - Relatório musical administrativo.",
        "Figura 37 - Manutenção de páginas.",
        "Figura 38 - Gestão de administradores.",
        "Figura 39 - Definições administrativas em tema escuro.",
        "Figura 40 - Definições administrativas em tema claro e idioma inglês.",
    ]:
        bullet(doc, item)

    heading(doc, "Lista de siglas", 1)
    add_table(doc, ["Sigla", "Significado"], [
        ["PAP", "Prova de Aptidão Profissional"],
        ["GPSI", "Gestão e Programação de Sistemas Informáticos"],
        ["CRUD", "Create, Read, Update, Delete, ou seja, criar, consultar, atualizar e eliminar dados"],
        ["DER", "Diagrama Entidade-Relacionamento"],
        ["SQL", "Structured Query Language, linguagem usada para consultar a base de dados"],
        ["UI", "Interface do utilizador"],
        ["PDF", "Portable Document Format, formato usado nas faturas/recibos"],
    ], [3, 12])

    heading(doc, "1. Introdução", 1)
    p(doc, "A Prova de Aptidão Profissional representa uma etapa importante do percurso no ensino profissional, porque exige a aplicação prática dos conhecimentos adquiridos ao longo do curso. No caso deste projeto, a opção foi desenvolver uma aplicação Web completa, com várias áreas funcionais, ligação a uma base de dados e funcionalidades reais de utilização, em vez de criar apenas uma página estática.")
    p(doc, "O projeto desenvolvido chama-se Greenerry e consiste numa plataforma Web de música, artistas e merchandising. A ideia central foi criar um espaço onde fosse possível descobrir artistas e músicas, consultar lançamentos, utilizar um leitor de música, comprar produtos associados aos artistas, gerir encomendas e controlar conteúdos através de uma administração própria.")
    p(doc, "A escolha deste tema teve origem no interesse por plataformas digitais ligadas à música e pela possibilidade de juntar, no mesmo projeto, várias áreas técnicas do curso: programação Web, base de dados, autenticação, gestão de sessões, formulários, consultas SQL, uploads, dashboards, segurança básica, geração de PDF, envio de e-mails e organização visual.")
    p(doc, "Ao longo do relatório são apresentadas as diferentes fases de trabalho, as escolhas técnicas, as funcionalidades implementadas e as dificuldades encontradas. Também são incluídas capturas de ecrã, tabelas de validação, dados de demonstração e referências ao repositório GitHub, de forma a mostrar que o projeto foi construído de forma progressiva e verificável.")
    add_figure(doc, FIG_DIR / "fig_home.png", "Figura 1 - Página inicial da Greenerry, com destaque para a identidade visual do projeto.")

    heading(doc, "1.1 Fundamentação da escolha", 2)
    p(doc, "A música é uma área que combina criatividade, tecnologia, comunicação e comércio digital. Muitas plataformas existentes separam a audição de música, a apresentação de artistas e a venda de produtos. A Greenerry foi pensada para experimentar uma solução integrada, onde o utilizador pudesse navegar por conteúdos musicais e comerciais sem sair do mesmo ambiente.")
    p(doc, "Esta escolha também permitiu trabalhar um sistema com vários perfis de utilização. Um visitante tem necessidades diferentes de um cliente autenticado; um artista precisa de ferramentas diferentes de um administrador; e a base de dados tem de suportar todos estes papéis sem perder coerência. Por isso, o tema foi adequado para uma PAP de GPSI, porque obrigou a planear uma aplicação com complexidade real.")

    heading(doc, "1.2 Finalidades do projeto", 2)
    p(doc, "A finalidade principal foi desenvolver uma aplicação Web funcional que pudesse ser demonstrada perante júri, com navegação, autenticação, base de dados, conteúdos, loja, música, relatórios e administração. Pretendeu-se que a aplicação fosse utilizável em ambiente local através do XAMPP, mas organizada de forma suficientemente clara para poder ser explicada, testada e mantida.")
    p(doc, "Outra finalidade foi consolidar autonomia no desenvolvimento. Durante a construção da Greenerry foi necessário interpretar problemas, procurar soluções, corrigir erros, ajustar o layout e testar fluxos completos, desde o registo de utilizador até à aprovação administrativa de produtos ou lançamentos.")

    heading(doc, "1.3 Enquadramento do projeto", 2)
    p(doc, "A Greenerry enquadra-se como uma plataforma Web de entretenimento e comércio eletrónico. Do lado público, apresenta músicas, artistas e produtos. Do lado privado, inclui contas de utilizador, favoritos, playlists, encomendas, notificações e mensagens. Do lado do artista, permite gerir conteúdos e acompanhar atividade. Do lado da administração, permite controlar a plataforma de forma centralizada.")
    p(doc, "O projeto foi desenvolvido em ambiente local, com Apache e MySQL/MariaDB através do XAMPP. O backend foi construído em PHP e a interface foi desenvolvida com HTML, CSS e JavaScript. Para funcionalidades específicas foram usadas bibliotecas como PHPMailer, para envio de e-mails, e Dompdf, para geração de faturas/recibos em PDF.")

    heading(doc, "2. Identificação do problema e público-alvo", 1)
    p(doc, "O problema identificado está relacionado com a fragmentação das ferramentas usadas por artistas e fãs. Um artista pode precisar de uma plataforma para divulgar músicas, outra para vender produtos, outra para comunicar com o público e outra para acompanhar estatísticas. Para o utilizador, esta separação também pode tornar a experiência menos direta.")
    p(doc, "A Greenerry procura responder a esta necessidade juntando várias funcionalidades num único website. O objetivo não foi criar uma plataforma comercial pronta para lançamento público, mas sim uma solução funcional de demonstração, capaz de provar que estes módulos podem trabalhar em conjunto.")
    heading(doc, "2.1 Público-alvo", 2)
    bullet(doc, "Visitantes que pretendem descobrir músicas, artistas e produtos sem criar conta imediatamente.")
    bullet(doc, "Utilizadores registados que querem guardar favoritos, criar playlists, seguir artistas, comprar produtos e consultar encomendas.")
    bullet(doc, "Artistas que pretendem gerir presença, lançamentos, merchandising, clientes e estatísticas.")
    bullet(doc, "Administradores responsáveis por validar conteúdos, acompanhar vendas, responder mensagens e gerir a plataforma.")

    heading(doc, "3. Objetivos do projeto", 1)
    heading(doc, "3.1 Objetivo geral", 2)
    p(doc, "O objetivo geral da Greenerry foi desenvolver uma plataforma Web funcional, responsiva e ligada a uma base de dados, capaz de integrar música, artistas, loja online, área de utilizador, área de artista e painel administrativo numa experiência coerente.")
    heading(doc, "3.2 Objetivos específicos", 2)
    for item in [
        "Criar um sistema de registo, login, logout, verificação de e-mail e recuperação de palavra-passe.",
        "Permitir ao utilizador introduzir o código recebido por e-mail na página de verificação ou recuperação, validando assim a conta ou a nova palavra-passe.",
        "Desenvolver páginas públicas para página inicial, música, artistas, lançamentos, loja e detalhes de produto.",
        "Implementar um leitor de música com fila de reprodução, capa, artista, título e controlos básicos.",
        "Criar favoritos, biblioteca pessoal, playlists e seguimento de artistas.",
        "Construir uma loja online com categorias, produtos, tamanhos, stock, carrinho, checkout, encomendas e recibos/faturas.",
        "Criar uma área de artista para uploads, produtos, lançamentos, encomendas, mensagens e estatísticas.",
        "Criar um painel administrativo para gerir utilizadores, música, géneros, lançamentos, produtos, categorias, encomendas, mensagens, relatórios e definições.",
        "Aplicar validação, permissões, sessões, CSRF e consultas preparadas em operações sensíveis.",
        "Preparar documentação, anexos, screenshots, diagramas e material para apresentação oral.",
    ]:
        bullet(doc, item)

    heading(doc, "4. Requisitos e entregáveis da PAP", 1)
    p(doc, "A PAP não depende apenas do website. Além do vídeo de utilização, a entrega deve incluir documentação e anexos que permitam compreender o projeto, instalar o sistema e avaliar o trabalho realizado. Por isso, o relatório considera tanto a aplicação como os documentos de apoio.")
    add_table(doc, ["Entregável", "Descrição", "Estado"], [
        ["Website funcional", "Projeto Greenerry executável em XAMPP, com Apache, MySQL/MariaDB e base de dados greenerry.", "Implementado"],
        ["Relatório da PAP", "Documento formal com introdução, desenvolvimento, conclusão, bibliografia, imagens e anexos.", "Em preparação final"],
        ["Manual técnico", "Documento com instalação, configuração, estrutura do projeto e resolução de problemas comuns.", "Estruturado"],
        ["Manual de utilização em vídeo", "Vídeo único com demonstração do lado USER e do lado ADMIN.", "A gravar após fecho do site"],
        ["DER/MER/FNN", "Diagramas da base de dados e normalização para anexos.", "Criado e ajustado"],
        ["GitHub", "Histórico de commits e versão do código no repositório remoto.", "Atualizado"],
    ], [3.7, 8.2, 3.2])
    heading(doc, "4.1 Requisitos funcionais principais", 2)
    add_table(doc, ["Área", "Requisito", "Evidência no projeto"], [
        ["Conta", "Registo, login, verificação de e-mail e recuperação de password", "pages/registar.php, pages/login.php, pages/verify_email.php, pages/reset_password.php"],
        ["Música", "Listagem, pesquisa, detalhes de lançamento e leitor", "pages/music.php, pages/release.php, assets/js/greenerry/player.js"],
        ["Utilizador", "Favoritos, playlists, perfil, notificações e histórico", "pages/favourites.php, pages/profile.php, pages/notifications.php, pages/my_orders.php"],
        ["Loja", "Produtos, carrinho, checkout, encomendas e fatura", "pages/shop.php, pages/cart.php, pages/checkout.php, pages/receipt.php"],
        ["Artista", "Dashboard, produtos, lançamentos, clientes, mensagens e análises", "pages/artist_dashboard.php, pages/artist_analytics.php"],
        ["Admin", "Dashboard, gestão de géneros, produtos, lançamentos, encomendas, mensagens e relatórios", "admin/dashboard.php, admin/genres.php, admin/reports.php"],
    ], [2.5, 5.5, 7.2])

    heading(doc, "5. Metodologia e planeamento", 1)
    p(doc, "A metodologia utilizada foi incremental. Em primeiro lugar foram identificadas as funcionalidades essenciais. Depois foram planeadas as páginas, a base de dados e os perfis de utilizador. A partir daí, o projeto foi desenvolvido por módulos, testando cada parte antes de avançar para a seguinte.")
    for step in [
        "Análise inicial do tema e definição das áreas principais: música, loja, utilizador, artista e administração.",
        "Modelação da base de dados, incluindo clientes, administradores, géneros, lançamentos, faixas, produtos, encomendas e mensagens.",
        "Criação das páginas públicas e definição da identidade visual escura da plataforma.",
        "Implementação de autenticação, sessões e permissões para utilizador e administrador.",
        "Desenvolvimento do leitor de música, da loja, do checkout e das faturas.",
        "Criação da área de artista e do painel administrativo com relatórios e moderação.",
        "Testes em XAMPP, correção de erros, ajustes de layout e preparação dos anexos.",
    ]:
        number(doc, step)
    add_table(doc, ["Fase", "Período", "Trabalho realizado"], [
        ["Análise", "setembro/outubro", "Escolha do tema, identificação do problema e levantamento de funcionalidades."],
        ["Planeamento", "outubro/novembro", "Estrutura inicial, base de dados, páginas e divisão por áreas."],
        ["Construção", "dezembro/abril", "Programação das funcionalidades, integração de base de dados e testes locais."],
        ["Ajustes finais", "maio/junho", "Correção de layout, dados de demonstração, screenshots, relatório e GitHub."],
        ["Apresentação", "junho", "Preparação do vídeo, defesa oral e anexos finais."],
    ], [3.4, 3.2, 8.5])

    heading(doc, "6. Ferramentas e tecnologias utilizadas", 1)
    p(doc, "As tecnologias escolhidas respeitam o contexto do curso e o objetivo de criar uma aplicação Web com backend, frontend e base de dados. A tabela seguinte resume as ferramentas principais e a sua função no projeto.")
    add_table(doc, ["Ferramenta/Tecnologia", "Utilização no projeto"], [
        ["HTML", "Estrutura das páginas públicas, área de utilizador, área de artista e administração."],
        ["CSS", "Layout escuro, responsividade, cartões, formulários, tabelas, sidebars e player."],
        ["JavaScript", "Interações da interface, leitor de música, carrinho e ações dinâmicas."],
        ["PHP", "Backend, sessões, validação, consultas, autenticação, permissões e páginas dinâmicas."],
        ["MySQL/MariaDB", "Base de dados da plataforma Greenerry."],
        ["XAMPP", "Ambiente local com Apache, PHP e MySQL/MariaDB."],
        ["Composer", "Gestão de dependências PHP."],
        ["PHPMailer", "Envio de e-mails de verificação, recuperação e notificações."],
        ["Dompdf", "Geração de recibos/faturas em PDF."],
        ["Git e GitHub", "Controlo de versões, commits, histórico e entrega remota do código."],
        ["diagrams.net", "Criação e organização dos diagramas de base de dados."],
    ], [5, 10])

    heading(doc, "7. Arquitetura do sistema", 1)
    p(doc, "A aplicação foi organizada em várias camadas. A interface Web apresenta páginas e componentes ao utilizador. O backend em PHP processa pedidos, valida dados, consulta a base de dados e aplica permissões. A base de dados MySQL/MariaDB guarda as entidades principais. Serviços auxiliares tratam e-mails, faturas, uploads e exportações.")
    add_figure(doc, arch, "Figura 2 - Arquitetura geral da plataforma Greenerry.")
    heading(doc, "7.1 Estrutura de pastas", 2)
    add_table(doc, ["Pasta/Ficheiro", "Função"], [
        ["admin/", "Painel administrativo e páginas de gestão interna."],
        ["api/", "Pontos de comunicação usados por ações assíncronas e moderação."],
        ["assets/", "CSS, JavaScript, imagens, capas, banners e recursos visuais."],
        ["includes/", "Configuração, ligação à base de dados, autenticação, helpers, e-mail e validação."],
        ["pages/", "Páginas públicas, área de utilizador e área de artista."],
        ["vendor/", "Dependências instaladas por Composer."],
        ["docs/", "Documentação, diagramas, relatório e anexos da PAP."],
        ["greenerry.sql", "Ficheiro de base de dados usado para importação local."],
    ], [4.5, 10.5])
    heading(doc, "7.2 Separação de responsabilidades", 2)
    p(doc, "A separação por pastas e ficheiros comuns reduziu repetição de código. Ficheiros como includes/config.php, includes/auth.php, includes/helpers.php, includes/email.php e includes/validation.php concentram funcionalidades usadas em várias páginas. Isto facilita alterações futuras, porque uma regra comum pode ser corrigida num único local.")
    heading(doc, "7.3 Fluxo de autenticação e permissões", 2)
    p(doc, "A autenticação foi desenhada para suportar dois tipos principais de sessão: sessão de utilizador/cliente e sessão de administrador. Esta separação foi necessária porque as páginas públicas, as páginas de artista e o painel administrativo não devem permitir as mesmas operações.")
    p(doc, "Quando um utilizador inicia sessão, o sistema guarda dados mínimos na sessão, como o identificador e o e-mail, e usa funções auxiliares para confirmar se existe utilizador autenticado. No caso da administração, a sessão é diferente e o sistema verifica se o administrador está ativo e se tem permissões para aceder à página pretendida.")
    p(doc, "Esta lógica ajuda a proteger áreas internas. Por exemplo, uma página de aprovação de produtos ou lançamentos não deve estar disponível para um cliente normal. Da mesma forma, uma área de artista deve carregar apenas informação associada ao artista em sessão.")
    heading(doc, "7.4 Instalação e execução em ambiente local", 2)
    p(doc, "O projeto foi preparado para funcionar em XAMPP. A pasta da aplicação fica em C:\\xampp\\htdocs\\dashboard\\greenerry e o acesso local é feito através de http://localhost/dashboard/greenerry/. Para a base de dados, é usada a base greenerry, com utilizador root e password vazia, seguindo a configuração local habitual do XAMPP.")
    add_table(doc, ["Passo", "Descrição"], [
        ["1", "Iniciar Apache e MySQL/MariaDB no painel do XAMPP."],
        ["2", "Confirmar que a pasta greenerry está dentro de C:\\xampp\\htdocs\\dashboard."],
        ["3", "Criar ou importar a base de dados greenerry através do phpMyAdmin."],
        ["4", "Confirmar que includes/config.php aponta para localhost, root e base de dados greenerry."],
        ["5", "Abrir http://localhost/dashboard/greenerry/ no navegador."],
        ["6", "Entrar com conta de demonstração de utilizador, artista ou administrador."],
    ], [2.2, 12.8])

    heading(doc, "8. Base de dados", 1)
    p(doc, "A base de dados é uma das partes centrais do projeto. Quase todas as funcionalidades dependem dela: contas, géneros, música, produtos, stock, encomendas, pagamentos, mensagens, notificações, playlists, favoritos e estatísticas. Por isso, a organização dos dados foi tratada como uma prioridade.")
    add_figure(doc, db_diag, "Figura 3 - Modelo de dados por módulos, com tabelas principais, PK/FK e relações essenciais.")
    heading(doc, "8.1 Principais tabelas", 2)
    add_table(doc, ["Tabela", "Responsabilidade"], [
        ["cliente", "Guarda utilizadores, artistas e dados de perfil."],
        ["admin", "Guarda contas administrativas e permissões de gestão."],
        ["genero", "Guarda géneros musicais ativos ou inativos, usados em filtros e estatísticas."],
        ["release_musical", "Guarda álbuns, EPs e singles enviados por artistas."],
        ["faixa", "Guarda músicas, duração, estado e ligação ao género/release."],
        ["categoria", "Classifica produtos da loja, como T-Shirt, Hoodie, Vinil, CD e Acessório."],
        ["produto", "Guarda merchandising, preço, stock, aprovação e artista associado."],
        ["encomenda", "Guarda compras, estados de pagamento, envio e totais."],
        ["encomenda_item", "Guarda cada produto dentro de uma encomenda."],
        ["pagamento", "Regista pagamentos e referências associadas às encomendas."],
        ["playlist / playlist_faixa", "Guarda listas de músicas e a ordem das faixas."],
        ["favorito_musica", "Guarda músicas favoritas de cada utilizador."],
        ["faixa_listen", "Regista reproduções, ouvintes e segundos ouvidos para estatísticas."],
        ["mensagem_admin", "Guarda mensagens de suporte entre utilizador e administração."],
        ["notificacao", "Guarda avisos de conta, encomenda, produto, música e sistema."],
    ], [4.5, 10.5])
    heading(doc, "8.2 Relações importantes", 2)
    for item in [
        "Um cliente pode comprar vários produtos através de várias encomendas.",
        "Uma encomenda pode conter vários itens, permitindo produtos de diferentes artistas na mesma compra.",
        "Um produto pertence a uma categoria e pode ter stock geral ou stock por tamanho.",
        "Um artista pode ter vários lançamentos musicais e vários produtos.",
        "Um género classifica lançamentos e faixas, permitindo que a administração, os filtros e os relatórios trabalhem com a mesma informação.",
        "Uma faixa pode aparecer em playlists, favoritos e registos de audição.",
        "As mensagens e notificações ligam ações da plataforma aos utilizadores certos.",
    ]:
        bullet(doc, item)
    heading(doc, "8.3 Dados de demonstração", 2)
    p(doc, "Para que os gráficos e relatórios apresentassem informação completa, foram preparados dados coerentes de demonstração. Estes dados não foram inseridos de forma aleatória: foram associados aos artistas, músicas, produtos e utilizadores já existentes, criando encomendas, pagamentos, favoritos, playlists, audições recentes, mensagens e itens pendentes de revisão.")
    add_table(doc, ["Entidade", "Total após preparação"], [
        ["Clientes", "17"],
        ["Administradores", "5"],
        ["Géneros", "8"],
        ["Lançamentos", "46"],
        ["Faixas", "190"],
        ["Produtos", "55"],
        ["Encomendas", "76"],
        ["Pagamentos", "76"],
        ["Playlists", "9"],
        ["Favoritos", "92"],
        ["Audições", "mais de 9000 registos"],
        ["Mensagens administrativas", "11"],
        ["Notificações", "86"],
    ], [6, 5])
    p(doc, "Foram criados scripts de seed para que estes dados possam ser reaplicados de forma controlada. Os scripts ficam em scripts/seed_pap_report_data.php e scripts/seed_pap_chart_data.php. Ambos usam uma chave de configuração na base de dados para evitar duplicação acidental.")
    heading(doc, "8.4 Normalização, MER e FNN", 2)
    p(doc, "A modelação da base de dados foi trabalhada para evitar duplicação excessiva de informação e para separar responsabilidades. A tabela cliente guarda dados da conta; a tabela produto guarda dados do produto; a tabela categoria guarda apenas a classificação do produto; e a tabela encomenda_item guarda os dados necessários para manter o histórico da compra.")
    p(doc, "A parte dos géneros musicais foi revista para ficar mais completa no MER/FNN. O género não deve estar representado apenas numa zona isolada do diagrama: ele influencia os lançamentos musicais, as faixas, os filtros de navegação e os relatórios. Por isso, a relação com release_musical e faixa é importante para que a base de dados permita analisar música por estilo.")
    p(doc, "Na prática, isto significa que um lançamento pode ter um género principal e que cada faixa também pode guardar o género associado. Esta redundância controlada permite flexibilidade, porque um álbum pode ter um género principal e, ao mesmo tempo, uma faixa específica pode ser classificada de forma mais precisa se for necessário.")
    add_table(doc, ["Aspeto", "Aplicação na Greenerry"], [
        ["1.ª Forma Normal", "Os campos guardam valores atómicos, como nome, e-mail, preço, estado e data."],
        ["2.ª Forma Normal", "Dados dependentes de entidades próprias foram separados em tabelas, como categoria, género e produto_imagem."],
        ["3.ª Forma Normal", "Informações que não dependem diretamente da chave principal foram deslocadas para entidades próprias, reduzindo repetição."],
        ["Histórico de compra", "A tabela encomenda_item guarda nome e preço do produto no momento da compra, preservando o recibo mesmo que o produto mude depois."],
        ["Género musical", "A tabela genero alimenta lançamentos, faixas, filtros, estatísticas e gestão administrativa."],
    ], [4.6, 10.4])
    heading(doc, "8.5 Estados e moderação de dados", 2)
    p(doc, "Várias tabelas usam campos de estado. Esta opção foi importante para permitir que um conteúdo possa existir na base de dados sem ficar imediatamente público. Produtos e lançamentos podem estar pendentes, aprovados, rejeitados ou inativos. Clientes podem estar ativos, inativos ou bloqueados. Encomendas têm estados próprios de pagamento e envio.")
    p(doc, "Esta lógica aproxima o projeto de uma aplicação real, porque a administração consegue rever conteúdos antes de os disponibilizar. Também facilita os relatórios, pois permite distinguir dados aprovados, pendentes, cancelados, pagos ou reembolsados.")

    heading(doc, "9. Desenvolvimento do projeto", 1)
    add_figure(doc, flow, "Figura 4 - Fluxos principais de compra, música e moderação.")
    heading(doc, "9.1 Frontend e experiência visual", 2)
    p(doc, "A interface foi construída com uma estética escura, adequada ao tema musical. Foram criadas páginas com cartões, grelhas, filtros, formulários, botões, sidebars, tabelas administrativas e um player fixo. A organização visual teve de ser ajustada várias vezes para evitar sobreposições, especialmente em páginas com muitos dados.")
    add_figure(doc, FIG_DIR / "fig_music_stable.png", "Figura 5 - Catálogo de música com cartões, filtros e navegação.")
    add_figure(doc, FIG_DIR / "fig_artists_stable.png", "Figura 6 - Listagem de artistas disponíveis na plataforma.")
    add_figure(doc, FIG_DIR / "fig_shop_stable.png", "Figura 7 - Loja online com produtos de merchandising.")
    heading(doc, "9.2 Backend em PHP", 2)
    p(doc, "O backend foi responsável por ligar a interface à base de dados. As páginas em PHP processam formulários, verificam permissões, carregam dados, validam entradas, gerem sessões e atualizam a base de dados. Em operações sensíveis foram usadas consultas preparadas, validação e tokens CSRF.")
    p(doc, "A autenticação permite distinguir utilizadores comuns, artistas e administradores. Esta distinção é importante porque cada perfil tem ações diferentes. Um administrador pode aprovar conteúdos, enquanto um artista pode gerir os seus próprios lançamentos e produtos.")
    heading(doc, "9.3 Verificação por e-mail e recuperação de palavra-passe", 2)
    p(doc, "O projeto inclui verificação de e-mail e recuperação de palavra-passe. Quando uma conta é registada, o sistema cria um código e envia-o por e-mail. O utilizador deve introduzir esse código na página pages/verify_email.php. No caso da recuperação de palavra-passe, o código é introduzido na página pages/reset_password.php, juntamente com a nova palavra-passe.")
    p(doc, "Esta funcionalidade usa tabelas próprias para guardar códigos temporários com data de expiração. Assim, a tabela principal de clientes não fica misturada com dados temporários de autenticação.")
    heading(doc, "9.4 Leitor de música", 2)
    p(doc, "O leitor de música foi uma das partes mais exigentes. Foi necessário guardar a faixa atual, apresentar a capa, o título e o artista, permitir reprodução, controlar a fila e manter a integração com várias páginas. O player também precisava de encaixar no layout sem tapar conteúdos importantes.")
    heading(doc, "9.4.1 Sidebars e painel a tocar", 3)
    p(doc, "A interface possui duas barras laterais importantes. A barra esquerda concentra a navegação principal, atalhos de conta, carrinho, biblioteca, compras, suporte e área de artista. A barra direita apresenta o painel A tocar, com capa da faixa, artista, ações de favorito/playlist e fila de reprodução. Ambas podem estar fechadas por defeito e abertas quando o utilizador precisa delas.")
    add_figure(doc, FIG_DIR / "fig_sidebars_open_player.png", "Figura 8 - Sidebars esquerda e direita abertas, com leitor ativo e fila de reprodução.")
    heading(doc, "9.5 Loja, encomendas e faturas", 2)
    p(doc, "A loja permite consultar produtos, adicionar ao carrinho, escolher quantidades, avançar para checkout e criar encomendas. Cada encomenda guarda subtotal, IVA, comissão, total final, estado de pagamento e estado de envio. Os itens da encomenda guardam uma cópia do nome, preço e categoria do produto, garantindo que a fatura continua correta mesmo que o produto seja alterado futuramente.")
    p(doc, "A geração de faturas/recibos em PDF foi implementada com Dompdf. O sistema também envia e-mails de confirmação e pode anexar a fatura, quando a configuração de e-mail está ativa.")
    heading(doc, "9.5.1 Detalhe de produto, carrinho e checkout", 3)
    p(doc, "O detalhe de produto mostra imagem, artista, categoria, descrição, preço, IVA, seleção de tamanho, quantidade, stock e botão de adicionar ao carrinho. Depois, o carrinho apresenta os produtos escolhidos, quantidade, tamanho, subtotal, IVA estimado e total. No checkout, o utilizador introduz dados de entrega e escolhe o método de pagamento demonstrativo.")
    add_figure(doc, FIG_DIR / "fig_product_detail_cart.png", "Figura 9 - Página de detalhe de produto com tamanho, stock e botão de adicionar ao carrinho.")
    add_figure(doc, FIG_DIR / "fig_cart_filled.png", "Figura 10 - Carrinho com produto selecionado, quantidade, IVA estimado e total.")
    add_figure(doc, FIG_DIR / "fig_checkout_filled.png", "Figura 11 - Checkout com formulário de entrega e resumo da encomenda.")
    heading(doc, "9.6 Área de artista", 2)
    p(doc, "A área de artista permite acompanhar estatísticas, gerir produtos, consultar encomendas relacionadas com os seus produtos, responder mensagens e analisar desempenho das faixas. Esta área demonstra que o projeto não é apenas uma loja ou um catálogo, mas uma plataforma com vários tipos de utilizador.")
    p(doc, "Um ponto importante desta área é a comunicação por encomenda. Cada compra pode ter uma pequena conversa associada entre comprador e artista, semelhante a um chat simples, permitindo esclarecer dúvidas sobre produtos, tamanhos, envio ou estado da encomenda sem sair da plataforma.")
    p(doc, "O artista pode acompanhar estados como pendente, em preparação, enviado, entregue ou cancelado, mas a moderação administrativa tem prioridade. Quando a administração bloqueia ou inativa um lançamento/produto, o artista não consegue reativá-lo sozinho; deve aguardar nova decisão administrativa.")
    add_figure(doc, FIG_DIR / "fig_artist_dashboard.png", "Figura 12 - Dashboard de artista com resumo de atividade.")
    add_figure(doc, FIG_DIR / "fig_artist_products.png", "Figura 13 - Produtos na área de artista, com stock, vendas e estado.")
    add_figure(doc, FIG_DIR / "fig_artist_analytics_seeded.png", "Figura 14 - Análises de artista com reproduções, ouvintes, horas de escuta e faixas.")
    heading(doc, "9.7 Painel administrativo", 2)
    p(doc, "O painel administrativo é a zona de controlo da plataforma. A administração pode consultar indicadores, gerir música, lançamentos, géneros, produtos, categorias, encomendas, utilizadores, mensagens, relatórios e definições. Esta área foi essencial para demonstrar CRUD, moderação, relatórios e tomada de decisão a partir de dados.")
    add_figure(doc, FIG_DIR / "fig_admin_genres.png", "Figura 15 - Gestão administrativa de géneros, com listagem, edição e estado.")
    add_figure(doc, FIG_DIR / "fig_admin_dashboard_seeded.png", "Figura 16 - Painel administrativo com dados de demonstração e gráficos preenchidos.")
    add_figure(doc, FIG_DIR / "fig_admin_reports_seeded.png", "Figura 17 - Relatório financeiro administrativo com categorias e valores.")
    heading(doc, "9.8 Gestão administrativa de géneros", 2)
    p(doc, "A gestão de géneros foi adicionada como uma funcionalidade própria da administração. Esta área permite listar géneros existentes, criar novos géneros, editar o nome e alterar o estado entre ativo e inativo. A existência desta gestão é importante porque os géneros aparecem em músicas, lançamentos, filtros e estatísticas.")
    p(doc, "Antes desta melhoria, a parte de géneros estava menos completa do ponto de vista administrativo. Ao criar uma página própria para géneros, a administração passou a conseguir controlar a taxonomia musical sem alterar diretamente a base de dados. Isto torna o sistema mais seguro e mais prático para utilização real.")
    p(doc, "Também foi necessário ajustar o layout desta página, porque os cartões de género precisavam de encaixar melhor no painel. O objetivo foi tornar a listagem mais legível, com campos de nome, estado, contadores de faixas/lançamentos e botão de guardar.")
    heading(doc, "9.9 Relatórios e indicadores", 2)
    p(doc, "A área de relatórios foi criada para transformar os dados da base de dados em informação útil. O painel apresenta receita paga, comissão da plataforma, base para artistas, valores cancelados/reembolsados, receita por categoria e gráficos temporais. Estes indicadores dependem de consultas SQL com junções entre encomendas, itens, produtos e categorias.")
    p(doc, "A dashboard principal também mostra informação de catálogo por rever, reproduções, ticket médio e evolução mensal. Para que estes gráficos ficassem completos na apresentação, foram adicionados dados coerentes de demonstração, incluindo encomendas históricas de janeiro a junho e conteúdos pendentes de aprovação.")
    p(doc, "A exportação para Excel no painel administrativo permite retirar dados para análise externa. Esta funcionalidade valoriza a PAP porque mostra que a aplicação não serve apenas para apresentar páginas, mas também para apoiar gestão e tomada de decisão.")
    heading(doc, "9.9.1 Revisão de produtos e encomendas", 3)
    p(doc, "A administração também consegue rever produtos pendentes, aprovar ou rejeitar submissões, consultar stock, preços, IVA e comissão. Na área de encomendas, é possível acompanhar estados de pagamento e envio, pesquisar encomendas e abrir faturas associadas.")
    add_figure(doc, FIG_DIR / "fig_admin_products_review.png", "Figura 18 - Revisão administrativa de produtos pendentes.")
    add_figure(doc, FIG_DIR / "fig_admin_orders.png", "Figura 19 - Gestão administrativa de encomendas e estados.")
    heading(doc, "9.10 Dados de demonstração para gráficos", 2)
    p(doc, "Durante a preparação final, foi percebido que alguns gráficos ficavam pobres se a base de dados tivesse poucos registos. Por isso, foram adicionados dados de demonstração com coerência: encomendas associadas a clientes existentes, produtos de artistas existentes, pagamentos ligados a encomendas, playlists com faixas reais, favoritos, notificações, mensagens e audições distribuídas por vários dias.")
    p(doc, "A decisão de criar scripts de seed, em vez de inserir tudo manualmente sem registo, tornou o processo mais transparente. O relatório consegue explicar de onde vieram os dados e o GitHub guarda o código que os criou.")

    heading(doc, "10. Funcionalidades implementadas", 1)
    heading(doc, "10.1 Funcionalidades do utilizador", 2)
    for item in [
        "Registo, login, logout, verificação de e-mail e recuperação de palavra-passe.",
        "Consulta de página inicial, músicas, lançamentos, artistas e produtos.",
        "Pesquisa e navegação por conteúdos musicais e comerciais.",
        "Leitor de música com capa, artista, título, fila e controlos.",
        "Favoritos, biblioteca, playlists e seguimento de artistas.",
        "Carrinho de compras, checkout, histórico de encomendas e faturas.",
        "Perfil, notificações e contacto com administração.",
    ]:
        bullet(doc, item)
    heading(doc, "10.2 Funcionalidades do artista", 2)
    for item in [
        "Gestão de perfil artístico.",
        "Upload de música e organização de lançamentos.",
        "Gestão de produtos de merchandising.",
        "Consulta de encomendas relacionadas com os seus produtos.",
        "Mensagens com compradores e acompanhamento de clientes.",
        "Dashboard e análises de reproduções, ouvintes e horas de escuta.",
    ]:
        bullet(doc, item)
    heading(doc, "10.3 Funcionalidades da administração", 2)
    for item in [
        "Dashboard com indicadores financeiros, catálogo por rever, ticket médio e reproduções.",
        "Gestão de utilizadores, administradores e estados de conta.",
        "Gestão de géneros musicais, incluindo criação, edição, listagem e ativação/inativação.",
        "Gestão de categorias, produtos, lançamentos, músicas e aprovação/rejeição de conteúdos.",
        "Gestão de encomendas, estados, mensagens, notificações e definições gerais.",
        "Relatórios financeiros, categorias, utilizadores, mensagens e exportação em Excel.",
        "Suporte a português/inglês e alternância de tema claro/escuro.",
    ]:
        bullet(doc, item)
    heading(doc, "10.4 Extras técnicos e valorizáveis", 2)
    add_table(doc, ["Extra", "Contributo para a PAP"], [
        ["Envio de e-mails", "Permite verificação de conta, recuperação de password, confirmação de encomenda e avisos de revisão."],
        ["Faturas PDF", "Gera recibos/faturas com Dompdf, aproximando a loja de um fluxo real."],
        ["Área de artista", "Adiciona um terceiro perfil de utilização, aumentando a complexidade do sistema."],
        ["Relatórios", "Transformam dados de encomendas, categorias e audições em indicadores visuais."],
        ["Exportação", "Permite retirar informação administrativa para ficheiros externos."],
        ["Multi-idioma", "Disponibiliza textos em português e inglês através de ficheiros de tradução."],
        ["Tema claro/escuro", "Permite alternância visual e mostra atenção à experiência do utilizador."],
        ["Scripts de dados", "Preenchem a base de dados de forma documentada e idempotente para demonstração."],
    ], [4, 11])

    heading(doc, "11. Testes e validação", 1)
    p(doc, "Os testes foram realizados em ambiente local com XAMPP, verificando os principais fluxos do website. A prioridade foi garantir que as páginas carregavam, que os formulários funcionavam, que os dados eram guardados corretamente e que os indicadores administrativos refletiam a informação da base de dados.")
    add_table(doc, ["Teste", "Resultado esperado", "Estado"], [
        ["Abrir página inicial", "Página carrega com artistas, música e navegação.", "Concluído"],
        ["Criar conta", "Utilizador é guardado e recebe fluxo de verificação.", "Concluído"],
        ["Introduzir código de e-mail", "Conta ou password é validada através do código recebido.", "Concluído"],
        ["Fazer login", "Sessão de utilizador ou admin é criada corretamente.", "Concluído"],
        ["Pesquisar música/produto", "Resultados filtrados aparecem sem quebrar layout.", "Concluído"],
        ["Usar leitor de música", "Faixa, capa e artista aparecem no player.", "Concluído"],
        ["Criar playlist/favorito", "Registos são guardados na base de dados.", "Concluído"],
        ["Adicionar ao carrinho", "Produto aparece no carrinho com quantidade correta.", "Concluído"],
        ["Finalizar compra", "Encomenda, itens, morada e pagamento são registados.", "Concluído"],
        ["Abrir fatura", "Recibo/fatura é gerado em PDF.", "Concluído"],
        ["Entrar no admin", "Dashboard mostra indicadores e ações de gestão.", "Concluído"],
        ["Gerir géneros", "Admin lista, edita e ativa/inativa géneros.", "Concluído"],
        ["Ver relatórios", "Receita, categorias e gráficos usam dados da base de dados.", "Concluído"],
    ], [4.8, 8, 2.4])
    heading(doc, "11.1 Validação dos gráficos e dados", 2)
    p(doc, "Depois da inserção dos dados de demonstração, os gráficos administrativos passaram a mostrar seis meses de atividade, produtos e lançamentos por rever, pagamentos, encomendas, categorias com receita, favoritos, playlists e audições. Isto ajuda a apresentar a PAP com dados suficientes para explicar o funcionamento real dos relatórios.")
    heading(doc, "11.2 Casos de teste por perfil", 2)
    add_table(doc, ["Perfil", "Cenário testado", "Resultado"], [
        ["Visitante", "Abrir página inicial, música, artistas e loja sem autenticação.", "Navegação pública disponível."],
        ["Cliente", "Criar conta, verificar e-mail, iniciar sessão e editar perfil.", "Conta funcional com sessão própria."],
        ["Cliente", "Guardar favoritos, criar playlist e seguir artistas.", "Dados guardados nas tabelas correspondentes."],
        ["Cliente", "Adicionar produtos ao carrinho e finalizar compra.", "Encomenda, itens, pagamento e morada registados."],
        ["Cliente", "Abrir fatura/recibo.", "PDF gerado com dados da encomenda."],
        ["Artista", "Consultar dashboard e análises.", "Reproduções, ouvintes e faixas aparecem com dados."],
        ["Artista", "Gerir produtos e lançamentos.", "Conteúdos podem ser submetidos para revisão."],
        ["Admin", "Aprovar/rejeitar produtos e lançamentos.", "Estados são atualizados e ficam refletidos no catálogo."],
        ["Admin", "Criar/editar géneros.", "Géneros ficam disponíveis para classificação musical."],
        ["Admin", "Consultar relatórios.", "Gráficos e totais refletem a base de dados."],
    ], [3, 7.2, 4.8])
    heading(doc, "11.3 Riscos verificados durante os testes", 2)
    p(doc, "Alguns riscos foram observados durante os testes. Em páginas com muitos cartões, existia risco de o texto não caber no espaço. Em páginas administrativas, existia risco de tabelas e gráficos ficarem vazios sem dados suficientes. No player, existia risco de o painel lateral ou o player fixo interferirem com o conteúdo principal.")
    p(doc, "Estes riscos foram reduzidos com ajustes de CSS, dados de demonstração, revisão das sidebars, melhoria da página de géneros e validação visual através de screenshots.")

    heading(doc, "12. Controlo de versões e GitHub", 1)
    p(doc, "O projeto foi acompanhado com Git e publicado no GitHub. O controlo de versões permitiu guardar alterações por etapas, recuperar histórico e demonstrar evolução. Os commits recentes mostram melhorias no painel de géneros, no MER/FNN, nas sidebars, no relatório e nos dados de demonstração.")
    add_figure(doc, FIG_DIR / "fig_github.png", "Figura 20 - Repositório GitHub usado para controlo de versões do projeto.")
    add_table(doc, ["Commit", "Data", "Descrição"], [
        ["d06e5ce", "08/06/2026", "Add PAP demo data seed scripts"],
        ["bfab384", "08/06/2026", "Add PAP report draft"],
        ["bfa7dbb", "08/06/2026", "Update MER FNN genre coverage"],
        ["bd8646f", "08/06/2026", "Default sidebars to closed"],
        ["32d7750", "08/06/2026", "Tighten admin genre layout"],
        ["200652d", "08/06/2026", "Add admin genre management"],
        ["6d30f3d", "08/06/2026", "Add playlist editing"],
        ["5755e5f", "08/06/2026", "Show saved playlist state in player"],
    ], [3, 3, 9])
    p(doc, "Repositório remoto: https://github.com/Greenerry/Greenerry-Streaming", first_line=False)
    heading(doc, "12.1 Importância do histórico de commits", 2)
    p(doc, "O histórico de commits é importante porque demonstra evolução. Em vez de apresentar apenas o resultado final, é possível observar melhorias sucessivas: criação de playlists, gestão de géneros, correções de layout, atualização do MER/FNN, relatório e preparação de dados para a PAP.")
    p(doc, "Na defesa oral, o GitHub pode ser usado como prova de trabalho contínuo. Também permite explicar que determinadas decisões foram tomadas em fases, por exemplo quando a página de géneros foi primeiro implementada e depois ajustada visualmente.")

    heading(doc, "12.2 Inventário completo de páginas e funcionalidades", 2)
    p(doc, "Para o relatório final, não basta mostrar apenas algumas páginas bonitas. É necessário explicar o que existe no projeto, quem pode aceder, que dados são usados e que validações protegem cada fluxo. Por isso, esta secção funciona como inventário completo da Greenerry, separando visitante, cliente autenticado, artista e administração.")
    p(doc, "Como a interface ainda pode sofrer pequenos ajustes visuais antes da apresentação, o relatório foi preparado para ser atualizável: quando uma página muda, substitui-se a figura dessa página e ajusta-se a respetiva descrição, mantendo a estrutura principal do documento.")
    add_figure(doc, FIG_DIR / "fig_admin_products_images.png", "Figura 21 - Tabela administrativa de produtos com imagens associadas.")

    heading(doc, "12.2.1 Visitante sem sessão iniciada", 3)
    p(doc, "Antes do login, a Greenerry permite descobrir conteúdo, mas bloqueia ações pessoais. O visitante pode navegar, pesquisar, abrir páginas públicas e ouvir música pelo leitor, mas ações como favoritos, playlists, carrinho e compras exigem autenticação.")
    add_table(doc, ["Página", "O que mostra", "Regras/validações"], [
        ["Início", "Apresenta música, artistas, produtos destacados, navegação e botão Ver site no contexto administrativo.", "Conteúdo público; carrega dados aprovados e ativos."],
        ["Música", "Lista faixas com pesquisa, filtros, capas, artista e player fixo.", "O visitante pode ouvir, mas favoritos e playlists pedem login."],
        ["Artistas", "Permite pesquisar artistas e abrir perfis públicos.", "Mostra artistas ativos e respetivos lançamentos/produtos."],
        ["Loja", "Mostra produtos aprovados com imagem, preço, categoria e estado.", "Comprar ou adicionar ao carrinho exige sessão."],
        ["Detalhe do produto", "Mostra imagem, descrição, preço, stock e opções.", "Se existir tamanho, o tamanho é obrigatório antes de avançar."],
        ["Login e registo", "Entradas para cliente e artista, criação de conta e recuperação.", "Valida e-mail, password, campos obrigatórios, CSRF e mensagens de erro."],
    ], [3.2, 6.2, 6.1])
    add_figure(doc, FIG_DIR / "fig_login_user_only.png", "Figura 22 - Login público sem botão direto de administração.")
    add_figure(doc, FIG_DIR / "fig_admin_login_reserved.png", "Figura 23 - Login reservado da administração com acesso por e-mail e palavra-passe.")

    heading(doc, "12.2.2 Cliente autenticado", 3)
    p(doc, "Depois de iniciar sessão, o utilizador passa a ter acesso a funções pessoais. O sistema apresenta opções adicionais no perfil e permite guardar preferências, criar biblioteca, consultar compras, comunicar com a administração e receber notificações.")
    add_table(doc, ["Página/área", "Função principal", "Regras/validações"], [
        ["Perfil", "Editar dados pessoais, foto, nome, e-mail e informações da conta.", "Campos obrigatórios, formatos válidos e sessão ativa."],
        ["Menu do perfil", "Acesso rápido a perfil, biblioteca, compras, carrinho, suporte e terminar sessão.", "As opções só aparecem quando existe sessão iniciada."],
        ["Biblioteca", "Organiza músicas favoritas, playlists e artistas seguidos.", "Favoritos e playlists são guardados por utilizador."],
        ["Carrinho", "Lista produtos selecionados, quantidades, tamanhos e totais.", "Quantidade não pode ultrapassar stock; produto com tamanho exige tamanho."],
        ["Checkout", "Regista morada, pagamento simulado, encomenda e itens comprados.", "Campos de morada e método de pagamento são obrigatórios."],
        ["Compras", "Mostra histórico de encomendas, estados e faturas/recibos.", "Cada cliente consulta apenas as suas encomendas."],
        ["Notificações", "Mostra avisos do sistema, compras, mensagens e alterações relevantes.", "Notificações pertencem ao utilizador autenticado."],
        ["Falar com o admin", "Permite enviar mensagens de suporte e consultar respostas.", "Mensagem obrigatória; conversa associada ao utilizador."],
    ], [3.2, 6.1, 6.2])
    add_figure(doc, FIG_DIR / "fig_profile_dropdown.png", "Figura 24 - Menu do perfil com atalhos após login.")
    add_figure(doc, FIG_DIR / "fig_user_profile.png", "Figura 25 - Perfil do utilizador autenticado e formulário de edição.")
    add_figure(doc, FIG_DIR / "fig_user_library.png", "Figura 26 - Biblioteca do utilizador com playlists, favoritos e artistas.")
    add_figure(doc, FIG_DIR / "fig_user_notifications.png", "Figura 27 - Notificações do utilizador autenticado.")
    add_figure(doc, FIG_DIR / "fig_user_support.png", "Figura 28 - Contacto do utilizador com a administração.")

    heading(doc, "12.2.3 Área de artista", 3)
    p(doc, "A área de artista existe para separar a gestão de conteúdo musical e merchandising da utilização normal de cliente. O artista consegue consultar dados do seu perfil, submeter músicas, criar produtos, acompanhar encomendas associadas aos seus produtos e analisar desempenho.")
    add_table(doc, ["Página/área", "Função principal", "Regras/validações"], [
        ["Dashboard de artista", "Mostra resumo de faixas, lançamentos, produtos, vendas e estatísticas.", "Dados filtrados pelo artista autenticado."],
        ["Upload de música", "Submissão de faixa, capa, ficheiro de áudio, género e lançamento.", "Valida ficheiros, campos obrigatórios e formato permitido."],
        ["Lançamentos", "Organiza singles, EPs e álbuns associados ao artista.", "Conteúdo pode ficar pendente até revisão administrativa."],
        ["Produtos de artista", "Lista merchandising criado pelo artista.", "Mostra estado, stock, preço e disponibilidade."],
        ["Upload de produto", "Permite criar produto com imagem, categoria, preço, stock e tamanhos.", "Valida imagem, preço, stock, categoria e tamanhos quando aplicável."],
        ["Análises", "Mostra reproduções, ouvintes, favoritos e desempenho musical.", "Consultas SQL juntam faixas, audições, utilizadores e datas."],
        ["Pedidos/encomendas", "Permite acompanhar preparação, envio, entrega e cancelamento de itens.", "Estados são refletidos no comprador, artista e administração."],
        ["Rendimento", "Mostra valores associados a vendas e desempenho.", "Dados calculados a partir de encomendas, itens, comissões e entregas."],
        ["Mensagens/clientes", "Apoia comunicação por encomenda entre comprador e artista.", "Funciona como uma conversa curta ligada à compra."],
    ], [3.4, 6.2, 5.9])
    add_figure(doc, FIG_DIR / "fig_artist_upload_music.png", "Figura 29 - Upload de música na área de artista.")
    add_figure(doc, FIG_DIR / "fig_artist_upload_merch.png", "Figura 30 - Upload de produto na área de artista.")

    heading(doc, "12.2.4 Administração", 3)
    p(doc, "A administração concentra a gestão global do sistema. Nesta área são tratados utilizadores, produtos, categorias, géneros, lançamentos, encomendas, mensagens, relatórios, manutenção de páginas, administradores, idioma, tema e ligação rápida para ver o site público.")
    add_table(doc, ["Página administrativa", "Objetivo", "Evidência no relatório"], [
        ["Dashboard", "Resumo de receita, encomendas, utilizadores, lançamentos e atividade.", "Figura 16."],
        ["Curadoria da homepage", "Escolher conteúdos destacados na página inicial.", "Figura 31."],
        ["Encomendas", "Consultar compras, estados, clientes, valores e produtos.", "Figuras 19 e 21."],
        ["Mensagens por encomenda", "Acompanhar conversas entre comprador e artista associadas a cada compra.", "Mostra contexto da encomenda no painel."],
        ["Produtos", "Rever produtos pendentes, aprovar/rejeitar e listar produtos com imagens.", "Figuras 18 e 21."],
        ["Categorias", "Listar, criar e editar categorias de produtos.", "Figura 32."],
        ["Lançamentos", "Rever singles, EPs e álbuns enviados por artistas.", "Figura 33."],
        ["Géneros", "Listar, criar, editar e ativar/inativar géneros musicais.", "Figura 15."],
        ["Utilizadores", "Gerir clientes e artistas registados.", "Figura 34."],
        ["Mensagens", "Responder a contactos enviados pelos utilizadores.", "Figura 35."],
        ["Relatório financeiro", "Analisar receita, pagamentos, encomendas e categorias.", "Figura 17."],
        ["Relatório musical", "Analisar faixas, lançamentos, favoritos, playlists e audições.", "Figura 36."],
        ["Manutenção de páginas", "Ativar/desativar áreas do site e controlar disponibilidade.", "Figura 37."],
        ["Administradores", "Gerir contas administrativas e permissões.", "Figura 38."],
        ["Definições", "Alternar tema claro/escuro, idioma PT/EN e opções de administração.", "Figuras 39 e 40."],
    ], [3.4, 7.3, 4.8])
    add_figure(doc, FIG_DIR / "fig_admin_home_curator.png", "Figura 31 - Curadoria da homepage no painel administrativo.")
    add_figure(doc, FIG_DIR / "fig_admin_categories.png", "Figura 32 - Gestão administrativa de categorias.")
    add_figure(doc, FIG_DIR / "fig_admin_releases.png", "Figura 33 - Revisão administrativa de lançamentos.")
    add_figure(doc, FIG_DIR / "fig_admin_users.png", "Figura 34 - Gestão administrativa de utilizadores.")
    add_figure(doc, FIG_DIR / "fig_admin_messages.png", "Figura 35 - Mensagens no painel administrativo.")
    add_figure(doc, FIG_DIR / "fig_admin_music_report.png", "Figura 36 - Relatório musical administrativo.")
    add_figure(doc, FIG_DIR / "fig_admin_maintenance.png", "Figura 37 - Manutenção de páginas.")
    add_figure(doc, FIG_DIR / "fig_admin_admins.png", "Figura 38 - Gestão de administradores.")
    add_figure(doc, FIG_DIR / "fig_admin_settings.png", "Figura 39 - Definições administrativas em tema escuro.")
    add_figure(doc, FIG_DIR / "fig_admin_settings_light_en.png", "Figura 40 - Definições administrativas em tema claro e idioma inglês.")

    heading(doc, "12.3 Segurança, autenticação e validações", 2)
    p(doc, "A segurança foi tratada de forma simples, mas adequada ao contexto do projeto. A entrada de administração deixou de estar exposta no login público. O utilizador normal vê apenas login e registo; a área administrativa usa uma página reservada e exige e-mail, palavra-passe, conta ativa e permissões de administrador.")
    add_table(doc, ["Área", "Validação aplicada", "Motivo"], [
        ["Login de cliente/artista", "E-mail, password, CSRF e regeneração da sessão.", "Evitar submissões inválidas e reduzir risco de sessão antiga."],
        ["Login de admin", "Página reservada, e-mail, password, conta ativa e permissão administrativa.", "Não expor o botão Admin no site público."],
        ["Verificação por e-mail", "Código enviado por e-mail e introduzido na página de verificação.", "Confirmar que a conta pertence ao utilizador."],
        ["Recuperação de password", "Código enviado por e-mail e usado na página de reset.", "Permitir recuperar acesso sem mostrar passwords antigas."],
        ["Carrinho/produto", "Sessão obrigatória, stock, quantidade e tamanho obrigatório quando existe.", "Evitar compras incompletas ou impossíveis."],
        ["Uploads", "Ficheiro obrigatório, tipo permitido, imagem/capa, áudio e campos principais.", "Evitar conteúdos incompletos ou ficheiros errados."],
        ["Admin CRUD", "Sessão administrativa, permissões e pedidos protegidos.", "Evitar alterações por utilizadores sem autorização."],
        ["Manutenção", "Páginas podem ser ativadas/inativadas no painel.", "Permitir controlar disponibilidade do site."],
        ["Prioridade administrativa", "Conteúdos bloqueados ou inativados pelo admin não podem ser reativados pelo artista.", "Garantir que a moderação central prevalece."],
        ["Tema e idioma", "Preferência guardada e aplicada no frontend/admin.", "Melhorar acessibilidade e apresentação PT/EN."],
    ], [3.2, 6.6, 5.7])

    heading(doc, "13. Resultados obtidos", 1)
    p(doc, "O resultado final é uma plataforma Web funcional, com várias áreas interligadas e suportadas por uma base de dados estruturada. A Greenerry permite navegar por músicas, artistas e produtos, criar conta, confirmar e-mail, utilizar funcionalidades pessoais, efetuar compras simuladas, consultar faturas, acompanhar encomendas e administrar conteúdos através de um painel próprio.")
    p(doc, "Do ponto de vista técnico, o projeto demonstra utilização de PHP, MySQL/MariaDB, HTML, CSS e JavaScript num sistema com autenticação, permissões, formulários, uploads, relatórios, consultas SQL, gráficos, geração de PDF e envio de e-mails. A existência de uma área de artista e de uma administração completa aumenta a complexidade e aproxima o trabalho de uma aplicação real.")
    p(doc, "Do ponto de vista visual, a aplicação apresenta uma identidade coerente. O tema escuro, os cartões de música, o player, as sidebars, a loja, os dashboards e as tabelas administrativas foram ajustados para que a experiência fosse consistente.")

    heading(doc, "14. Dificuldades sentidas", 1)
    heading(doc, "14.1 Base de dados", 2)
    p(doc, "A base de dados foi uma das maiores dificuldades. O projeto tem muitas entidades relacionadas e uma alteração numa tabela podia afetar várias páginas. Foi necessário pensar nas chaves estrangeiras, nos estados dos conteúdos, nos totais das encomendas, nos pagamentos, nos favoritos, nas playlists e nas estatísticas.")
    heading(doc, "14.2 Consultas SQL", 2)
    p(doc, "As consultas SQL exigiram atenção, porque muitas páginas precisavam de juntar várias tabelas. Por exemplo, os relatórios financeiros dependem de encomendas, itens, produtos, categorias e pagamentos. As análises de artista dependem de faixas, lançamentos, audições e utilizadores. Um erro pequeno podia duplicar resultados ou apresentar dados errados.")
    heading(doc, "14.3 Leitor de música", 2)
    p(doc, "O leitor de música também foi difícil porque precisava de funcionar em várias páginas e manter a informação correta. Foi necessário coordenar JavaScript, HTML, dados das faixas e estado visual do player.")
    heading(doc, "14.4 Layout e responsividade", 2)
    p(doc, "A gestão do layout exigiu muitos ajustes. Algumas páginas tinham cards, tabelas, sidebars, formulários, gráficos e player no mesmo ecrã. Foi necessário rever espaçamentos, alturas, grelhas, comportamento das sidebars e encaixe dos géneros no painel administrativo.")
    heading(doc, "14.5 Organização geral", 2)
    p(doc, "Como o projeto cresceu bastante, tornou-se difícil manter todas as funcionalidades organizadas. A existência de páginas públicas, área de utilizador, área de artista e administração exigiu cuidado para não misturar permissões nem repetir código desnecessariamente.")

    heading(doc, "15. Resolução das dificuldades", 1)
    p(doc, "As dificuldades foram resolvidas através de testes sucessivos, pesquisa, reorganização do código e divisão do problema por partes. Em vez de alterar tudo ao mesmo tempo, cada módulo foi testado isoladamente e depois integrado no conjunto da plataforma.")
    add_table(doc, ["Dificuldade", "Solução aplicada"], [
        ["Base de dados complexa", "Separação por entidades, uso de chaves estrangeiras e criação de tabelas específicas para dados temporários."],
        ["Consultas SQL difíceis", "Testes diretos no MySQL, ajustes progressivos e separação de consultas por objetivo."],
        ["Player de música", "Organização do JavaScript do player e testes em diferentes páginas."],
        ["Layout", "Ajustes de CSS, grelhas responsivas, revisão das sidebars e melhor encaixe dos componentes."],
        ["Administração", "Criação de páginas próprias para géneros, produtos, lançamentos, encomendas, mensagens e relatórios."],
        ["Documentação", "Organização de screenshots, diagramas, checklist PAP, scripts de dados e relatório final."],
    ], [5, 10])

    heading(doc, "16. Conclusão e análise crítica", 1)
    p(doc, "A realização da PAP permitiu aplicar conhecimentos adquiridos ao longo do curso num projeto prático, completo e com várias áreas técnicas. A Greenerry não ficou limitada a páginas estáticas; tornou-se uma aplicação com base de dados, autenticação, permissões, loja, música, área de artista, administração, relatórios, e-mails e faturas.")
    p(doc, "O projeto exigiu autonomia e persistência. As maiores aprendizagens surgiram nas partes mais difíceis: planeamento da base de dados, criação de consultas SQL, construção do leitor de música, gestão de layout e integração de várias funcionalidades num único sistema.")
    p(doc, "Analisando criticamente o trabalho, existem aspetos que poderiam ser melhorados se houvesse mais tempo, principalmente segurança avançada, pagamentos reais, testes automáticos e otimização para produção. No entanto, para o contexto da PAP, a plataforma cumpre os objetivos principais e demonstra um conjunto alargado de competências de programação e organização de projeto.")

    heading(doc, "17. Melhorias futuras", 1)
    for item in [
        "Integração com pagamentos reais, como MB WAY, cartão bancário ou PayPal.",
        "Sistema de recomendações musicais baseado em géneros, favoritos e histórico de audição.",
        "Chat em tempo real entre utilizadores, artistas e administração.",
        "Aplicação mobile ou versão progressiva para telemóveis.",
        "Melhoria das estatísticas para artistas, com mais filtros e exportações.",
        "Moderação mais automatizada de conteúdos enviados por artistas.",
        "Testes automáticos para reduzir regressões no backend e frontend.",
        "Melhorias de acessibilidade, desempenho e segurança para ambiente de produção.",
        "Integração com serviços externos de streaming ou distribuição musical.",
    ]:
        bullet(doc, item)
    heading(doc, "17.1 Limitações reconhecidas", 2)
    p(doc, "Apesar de o projeto estar funcional para demonstração, existem limitações naturais. O sistema corre em ambiente local e não inclui configuração completa de produção. Os pagamentos são simulados ou registados internamente, não existindo ligação real a uma entidade bancária. A segurança foi trabalhada ao nível adequado para a PAP, mas uma versão pública exigiria auditoria, logs, limitação de tentativas, proteção reforçada contra abuso e backups automáticos.")
    p(doc, "Outra limitação é a ausência de uma aplicação mobile nativa. A interface é responsiva, mas uma aplicação real para telemóvel poderia melhorar notificações, reprodução de música e experiência de compra. Também seria necessário tratar direitos de autor, licenciamento musical e gestão legal de conteúdos se a plataforma fosse usada fora do contexto escolar.")

    heading(doc, "18. Referências bibliográficas / webgrafia", 1)
    refs = [
        "Apache Friends. (2026). XAMPP. Disponível em https://www.apachefriends.org/",
        "Dompdf. (2026). Dompdf. Disponível em https://github.com/dompdf/dompdf",
        "JGraph Ltd. (2026). diagrams.net. Disponível em https://www.diagrams.net/",
        "MariaDB Foundation. (2026). MariaDB Server Documentation. Disponível em https://mariadb.com/kb/en/documentation/",
        "Mozilla. (2026). MDN Web Docs. Disponível em https://developer.mozilla.org/",
        "Oracle. (2026). MySQL Documentation. Disponível em https://dev.mysql.com/doc/",
        "PHPMailer. (2026). PHPMailer. Disponível em https://github.com/PHPMailer/PHPMailer",
        "The PHP Group. (2026). PHP Manual. Disponível em https://www.php.net/manual/",
    ]
    for ref in refs:
        p(doc, ref, first_line=False)

    heading(doc, "19. Anexos", 1)
    p(doc, "Os anexos complementam o relatório e devem acompanhar a entrega final da PAP. Estes materiais ajudam a demonstrar a estrutura técnica, o funcionamento visual e a preparação da apresentação.")
    add_table(doc, ["Anexo", "Conteúdo"], [
        ["Anexo I", "DER/MER/FNN da base de dados Greenerry."],
        ["Anexo II", "Screenshots finais da aplicação."],
        ["Anexo III", "Checklist de requisitos e validação da PAP."],
        ["Anexo IV", "Manual técnico de instalação e configuração."],
        ["Anexo V", "Guião do vídeo de utilização USER + ADMIN."],
        ["Anexo VI", "Histórico GitHub e commits principais."],
        ["Anexo VII", "Scripts de seed usados para preencher dados de demonstração."],
    ], [3, 12])
    heading(doc, "19.1 Anexo técnico: scripts de seed", 2)
    p(doc, "Foram criados dois scripts para preencher a base de dados com dados coerentes: scripts/seed_pap_report_data.php e scripts/seed_pap_chart_data.php. O primeiro acrescenta pagamentos, encomendas, playlists, favoritos, audições, mensagens e notificações. O segundo acrescenta produtos e lançamentos pendentes, além de encomendas históricas para preencher gráficos mensais.")
    p(doc, "Estes scripts foram executados localmente e enviados para o GitHub no commit d06e5ce, garantindo que a origem dos dados de demonstração fica documentada.")
    heading(doc, "19.2 Anexo de instalação resumida", 2)
    p(doc, "Para executar o projeto noutra máquina com XAMPP, deve-se copiar a pasta Greenerry para o diretório htdocs, iniciar Apache e MySQL/MariaDB, importar a base de dados greenerry e abrir o endereço local no navegador. Caso a pasta vendor não exista, é necessário executar composer install para instalar PHPMailer e Dompdf.")
    heading(doc, "19.3 Anexo de demonstração oral", 2)
    p(doc, "Na apresentação oral, recomenda-se demonstrar primeiro o lado público do website, depois o fluxo de utilizador, a seguir a área de artista e, por fim, o painel administrativo. Esta ordem ajuda o júri a perceber a plataforma de fora para dentro: visitante, cliente, artista e administração.")

    doc.save(OUT)
    print(OUT)


if __name__ == "__main__":
    build()
