import os

def create_pdf(filename):
    os.makedirs(os.path.dirname(filename), exist_ok=True)
    
    # We can write a clean, valid PDF 1.4 file
    content = """BT
/F1 18 Tf
50 750 Td
(KIBO PET'S HOME - UY NHIEM CHI GIAI NGAN QUY CUU TRO) Tj
0 -30 Td
/F1 12 Tf
(Ma chung tu: CT001 - Ngay thuc hien: 26/10/2026) Tj
0 -25 Td
(Don vi phat lenh: Cong ty TNHH Thuong Mai KIBO Pet's Home) Tj
0 -20 Td
(Tai khoan trich quy: Quy cuu tro dong vat KIBO) Tj
0 -25 Td
(Don vi thu huong: Tram Cuu Ho Dong Vat Nong Lam) Tj
0 -20 Td
(Nguoi dai dien: Bac si thu y Nguyen Van An - SDT: 0912345678) Tj
0 -20 Td
(Dia chi: Khuon vien DH Nong Lam, TP. Thu Duc, TP.HCM) Tj
0 -25 Td
(So tien giai ngan: 14.250 VND) Tj
0 -20 Td
(Bang chu: Muoi bon nghin hai tram nam muoi dong) Tj
0 -25 Td
(Muc dich: Chi phi thuc an hat va dieu tri phau thuat triet san 35 ca cho meo) Tj
0 -40 Td
(Trang thai: GIAO DICH THANH CONG - NGAN HANG DA DOI SOAT) Tj
0 -60 Td
(Ke toan truong                              Giam doc quy) Tj
0 -20 Td
(  [Da ky]                                     [Da ky]  ) Tj
ET"""
    
    stream_len = len(content.encode('utf-8'))
    
    pdf = f"""%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length {stream_len} >>
stream
{content}
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000234 00000 n 
0000000295 + len(stream) 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
500
%%EOF
"""
    # Write robust PDF
    # Actually let's use a standard robust generator or python script
create_pdf("C:/xampp/htdocs/kibo/assets/docs/test.pdf")
print("OK")
