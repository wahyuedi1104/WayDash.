from fastapi import FastAPI, UploadFile, File, Form, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd
import io
import re
from datetime import datetime, timedelta, timezone
import gspread
from google.oauth2.service_account import Credentials
from gspread_dataframe import set_with_dataframe, get_as_dataframe

app = FastAPI(title="WayDash Engine API", version="10.9.1 - HSA Mapping")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

SCOPES = ['https://www.googleapis.com/auth/spreadsheets', 'https://www.googleapis.com/auth/drive']
CREDENTIALS_FILE = 'credentials.json' 
SPREADSHEET_ID = '1s_15KSrELtXDNb63qyFeHlzC4BHfqY1GworuxvJ9_24'

global_df = pd.DataFrame()
worksheet = None

try:
    credentials = Credentials.from_service_account_file(CREDENTIALS_FILE, scopes=SCOPES)
    gc = gspread.authorize(credentials)
    sh = gc.open_by_key(SPREADSHEET_ID)
    worksheet = sh.sheet1
    print("✅ BERHASIL TERHUBUNG KE GOOGLE SHEETS")
except Exception as e:
    print(f"❌ GAGAL KONEKSI KE GOOGLE SHEETS: {e}")

def sync_to_gsheets_background(df: pd.DataFrame):
    if worksheet is not None:
        try:
            worksheet.clear()
            set_with_dataframe(worksheet, df)
            print("✅ [BACKGROUND TASK] Selesai update ke Google Sheets.")
        except Exception as e:
            print(f"❌ [BACKGROUND TASK] Gagal sync ke GSheets: {e}")

@app.post("/process-data")
async def process_data(
    background_tasks: BackgroundTasks,
    file: UploadFile = File(None),
    start_date: str = Form(None),
    end_date: str = Form(None),
    selected_datel: str = Form(None)
):
    global global_df
    
    try:
        tz_wib = timezone(timedelta(hours=7))
        waktu_sekarang_obj = datetime.now(tz_wib)
        waktu_sekarang = waktu_sekarang_obj.strftime('%H:%M WIB')
        hari_ini_str_real = str(waktu_sekarang_obj.date())

        if file is not None and file.filename != "":
            contents = await file.read()
            if file.filename.endswith('.csv'):
                df_raw = pd.read_csv(io.BytesIO(contents), low_memory=False)
            else:
                df_raw = pd.read_excel(io.BytesIO(contents))
            
            df_raw.columns = [str(c).upper().strip() for c in df_raw.columns]
            global_df = df_raw.copy()
            background_tasks.add_task(sync_to_gsheets_background, df_raw)
            
            start_date = hari_ini_str_real
            end_date = hari_ini_str_real
            
        else:
            if global_df.empty:
                df_raw = get_as_dataframe(worksheet, evaluate_formulas=True).dropna(how='all', axis=0).dropna(how='all', axis=1)
                if df_raw.empty: return {"status": "error", "message": "Database Google Sheets kosong!"}
                global_df = df_raw.copy()
            else:
                df_raw = global_df.copy()

        # =================================================================
        # BULLETPROOF COLUMN VALIDATOR
        # =================================================================
        col_sto = 'STO' if 'STO' in df_raw.columns else ('SERVICE_AREA' if 'SERVICE_AREA' in df_raw.columns else 'STO')
        if col_sto not in df_raw.columns: df_raw[col_sto] = "UNKNOWN"

        col_dc = 'DATECREATED' if 'DATECREATED' in df_raw.columns else ('DATE_CREATED' if 'DATE_CREATED' in df_raw.columns else 'DATECREATED')
        if col_dc not in df_raw.columns: df_raw[col_dc] = pd.NaT

        col_sd = 'STATUSDATE' if 'STATUSDATE' in df_raw.columns else ('STATUS_DATE' if 'STATUS_DATE' in df_raw.columns else 'STATUSDATE')
        if col_sd not in df_raw.columns: df_raw[col_sd] = pd.NaT

        col_manja = 'TGL_MANJA' if 'TGL_MANJA' in df_raw.columns else ('TGLMANJA' if 'TGLMANJA' in df_raw.columns else 'TGL_MANJA')
        if col_manja not in df_raw.columns: df_raw[col_manja] = pd.NaT

        col_datel = 'DATEL_BARU' if 'DATEL_BARU' in df_raw.columns else ('DATEL' if 'DATEL' in df_raw.columns else ('DATEL BARU' if 'DATEL BARU' in df_raw.columns else 'DATEL'))
        if col_datel not in df_raw.columns: df_raw[col_datel] = "UNKNOWN"

        col_wonum = 'WONUM' if 'WONUM' in df_raw.columns else ('WO_NUM' if 'WO_NUM' in df_raw.columns else 'WONUM')
        if col_wonum not in df_raw.columns: df_raw[col_wonum] = "UNKNOWN"

        if 'STATUS' not in df_raw.columns: df_raw['STATUS'] = "UNKNOWN"
        # =================================================================
        
        df_raw[col_manja] = pd.to_datetime(df_raw[col_manja], errors='coerce').dt.date
        df_raw[col_sd] = pd.to_datetime(df_raw[col_sd], errors='coerce', dayfirst=True)
        df_raw['DATE_STR'] = df_raw[col_sd].dt.strftime('%Y-%m-%d')
        
        all_dates = [str(x) for x in df_raw['DATE_STR'].dropna().unique()]
        valid_dates = [x for x in all_dates if x <= hari_ini_str_real]
        list_tanggal_unik = sorted(valid_dates, reverse=True)
        list_datel_unik = sorted([str(x).strip() for x in df_raw[col_datel].dropna().unique() if str(x).strip() != 'UNKNOWN']) 

        if not start_date or start_date == "undefined" or start_date == "":
            active_start = active_end = hari_ini_str_real
        else:
            active_start, active_end = start_date, end_date
            
        df_datel_filtered = df_raw.copy()
        active_datels = [x.strip() for x in selected_datel.split(",")] if selected_datel and selected_datel not in ["undefined", "ALL", ""] else []
        if active_datels and col_datel in df_datel_filtered.columns:
            df_datel_filtered = df_datel_filtered[df_datel_filtered[col_datel].isin(active_datels)]

        df_filtered = df_datel_filtered[(df_datel_filtered['DATE_STR'] >= active_start) & (df_datel_filtered['DATE_STR'] <= active_end)].copy()
        all_stos = sorted([str(x).strip() for x in df_filtered[col_sto].dropna().unique() if str(x).strip() != 'UNKNOWN'])
        
        hari_ini_data = pd.to_datetime(active_end).date() if active_end else waktu_sekarang_obj.date()
        besok_data = hari_ini_data + timedelta(days=1)
        kemarin_data = hari_ini_data - timedelta(days=1)
        tiga_hari_lalu = hari_ini_data - timedelta(days=3)
        list_jam = list(range(8, 21))

        days_id = ["SENIN", "SELASA", "RABU", "KAMIS", "JUMAT", "SABTU", "MINGGU"]
        hari_ini_nama = days_id[hari_ini_data.weekday()]

        d, m, y = hari_ini_data.day, hari_ini_data.month, hari_ini_data.year
        y2 = str(y)[-2:]
        months_id = ["", "JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI", "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER"]
        short_months_id = ["", "JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"]
        
        today_date_pattern = re.compile(fr'\b0?{d}[/\-]0?{m}[/\-]({y}|{y2})\b|\b0?{d}\s+({months_id[m]}|{short_months_id[m]})\b', re.IGNORECASE)
        tunggu_konfirm_pattern = re.compile(r'\b(TUNGGU|NUNGGU|MENUNGGU|BELUM ADA|BLM ADA|BUTUH)\s+(KONFIRMASI|KONFIRM|INFO|INFORMASI|KABAR|RESPON)\b', re.IGNORECASE)

        df_filtered['JAM_RE'] = pd.to_datetime(df_filtered[col_dc], errors='coerce').dt.hour
        df_filtered['DATE_CREATED_STR'] = pd.to_datetime(df_filtered[col_dc], errors='coerce').dt.strftime('%Y-%m-%d')
        df_filtered['JAM_PS'] = df_filtered[col_sd].dt.hour

        # ====== DATA HSA MASTER ======
        HSA_MAPPING = {
            "BIN": "FAJAR", "CID": "YUSUF", "CPE": "ALFIN", "CPP": "EKO P",
            "CWA": "EKO C", "GAN": "HABIAN", "GBC": "TUKOT", "GBI": "EKO A",
            "JAG": "BAMBANG", "JTN": "KELLY", "KAL": "IQBAL", "KBY": "REFVIAN",
            "KLD": "RIVALDI", "KMG": "IQBAL", "KMY": "EKO A", "KRG": "SAMSUDIN",
            "PDK": "YUDHI", "PGB": "YUDHI", "PGG": "LEON", "PSM": "HARIS",
            "PSR": "EKO C", "RMG": "RIZKI AMIN", "TBE": "EKO M"
        }

        # ====== REPORT 1 ======
        df_ps = df_filtered[df_filtered['STATUS'] == 'COMPWORK'].copy()
        df_re = df_filtered[(df_filtered['DATE_CREATED_STR'] >= active_start) & (df_filtered['DATE_CREATED_STR'] <= active_end)].copy()
        
        r1_data = []
        tot_r1_re, tot_r1_ps = 0, 0
        for sto in all_stos:
            # Inject nama HSA dari Mapping di atas
            row = {"STO": sto, "HSA": HSA_MAPPING.get(sto.upper(), "-")}
            
            tot_re, tot_ps = 0, 0
            for j in list_jam:
                re_cnt = int((df_re[(df_re[col_sto] == sto) & (df_re['JAM_RE'] == j)]).shape[0])
                ps_cnt = int((df_ps[(df_ps[col_sto] == sto) & (df_ps['JAM_PS'] == j)]).shape[0])
                row[f"{j}_RE"], row[f"{j}_PS"] = re_cnt, ps_cnt
                tot_re += re_cnt; tot_ps += ps_cnt
            row["TOT_RE"], row["TOT_PS"] = tot_re, tot_ps
            row["ACHIEV_NUM"] = float(f"{(tot_ps / tot_re * 100):.1f}") if tot_re > 0 else 0.0
            row["ACHIEV"] = f"{row['ACHIEV_NUM']:.1f}%"
            r1_data.append(row); tot_r1_re += tot_re; tot_r1_ps += tot_ps
            
        sorted_r1 = sorted([x for x in r1_data if x["TOT_RE"] > 0], key=lambda k: k["ACHIEV_NUM"], reverse=True)
        top_5, bottom_5 = [{"STO": s["STO"], "ACHIEV": s["ACHIEV"]} for s in sorted_r1[:5]], [{"STO": s["STO"], "ACHIEV": s["ACHIEV"]} for s in reversed(sorted_r1[-5:])]
        grand_achiev_pct = f"{(tot_r1_ps / tot_r1_re * 100):.1f}%" if tot_r1_re > 0 else "0.0%"

        # ====== REPORT 2 ======
        r2_data = []
        for sto in all_stos:
            df_sto = df_filtered[df_filtered[col_sto] == sto]
            s_counts = df_sto['STATUS'].value_counts()
            df_wf = df_sto[df_sto['STATUS'] == 'WORKFAIL']
            k_tek = int(df_wf['ERRORCODE_AKHIR'].str.contains('TEKNIK', case=False, na=False).sum()) if 'ERRORCODE_AKHIR' in df_wf.columns else 0
            k_plg = int(df_wf['ERRORCODE_AKHIR'].str.contains('PELANGGAN|PLG', case=False, na=False).sum()) if 'ERRORCODE_AKHIR' in df_wf.columns else 0
            m_exp, m_hi, m_h1 = 0, 0, 0
            if col_manja in df_datel_filtered.columns:
                df_sto_unfiltered = df_datel_filtered[df_datel_filtered[col_sto] == sto]
                df_manja = df_sto_unfiltered[~df_sto_unfiltered['STATUS'].isin(['COMPWORK','WORKFAIL','CANCLWORK'])]
                m_exp, m_hi, m_h1 = int((df_manja[col_manja] < hari_ini_data).sum()), int((df_manja[col_manja] == hari_ini_data).sum()), int((df_manja[col_manja] == besok_data).sum())
            r2_data.append({"STO": sto, "INSTCOMP": int(s_counts.get('INSTCOMP', 0)), "ACTCOMP": int(s_counts.get('ACTCOMP', 0)), "VALSTART": int(s_counts.get('VALSTART', 0)), "VALCOMP": int(s_counts.get('VALCOMP', 0)), "K_TEKNIK": k_tek, "K_PLG": k_plg, "MANJA_EXP": m_exp, "MANJA_HI": m_hi, "MANJA_H1": m_h1})

        # ====== REPORT 3 ======
        df_r3 = df_filtered.copy()
        col_err = 'ERRORCODE_AKHIR' if 'ERRORCODE_AKHIR' in df_r3.columns else 'ERRORCODE'
        col_sub = 'SUBERRORCODE_AKHIR' if 'SUBERRORCODE_AKHIR' in df_r3.columns else 'SUBERRORCODE'
        for col in [col_err, col_sub]:
            if col not in df_r3.columns: df_r3[col] = ""
            df_r3[col] = df_r3[col].fillna("").astype(str).str.strip()
        df_r3.loc[df_r3['STATUS'] != 'WORKFAIL', [col_err, col_sub]] = ""
        r3_pivot_data = []
        df_wf_only = df_r3[(df_r3['STATUS'] == 'WORKFAIL') & (df_r3[col_sub] != "")]
        most_kendala = f"{df_wf_only[col_sub].value_counts().idxmax()} ({df_wf_only[col_sub].value_counts().max()})" if not df_wf_only.empty else "-"
        try:
            df_counts = df_r3.groupby(['STATUS', col_err, col_sub, col_sto]).size().unstack(fill_value=0).reindex(columns=all_stos, fill_value=0)
            for idx, row in df_counts.iterrows():
                row_dict = {"STATUS": str(idx[0]), "ERROR_KAT": str(idx[1]), "SUB_ERR": str(idx[2]), "All": sum(row)}
                for s in all_stos: row_dict[s] = int(row[s])
                r3_pivot_data.append(row_dict)
        except Exception: pass
        
        s_val = df_r3['STATUS'].value_counts()
        ps_v = int(s_val.get('COMPWORK', 0))
        aktivasi_v = int(s_val.get('ACTCOMP', 0)) + int(s_val.get('VALSTART', 0)) + int(s_val.get('VALCOMP', 0))
        pi_v = int(s_val.get('STARTWORK', 0))
        pi_prog_v = int(s_val.get('INSTCOMP', 0)) + int(s_val.get('CONTWORK', 0)) + int(s_val.get('PENDWORK', 0))
        wf_v = int(s_val.get('WORKFAIL', 0))
        est_ps = ps_v + aktivasi_v
        
        wa_text = f"PS (COMPWORK) = {ps_v}\nAKTIVASI (ACTCOMP+VALSTART+VALCOMP) = {aktivasi_v}\nPI (STARTWORK) = {pi_v}\nPI PROGRESS (INSTCOMP+CONTWORK+PENDWORK) = {pi_prog_v}\nKENDALA (WORKFAIL) = {wf_v}\nEST PS (PS+AKTIVASI) = {est_ps}"

        # ====== REPORT 4 ======
        df_r4_base = df_datel_filtered.copy()
        df_r4_base = df_r4_base.sort_values(by=[col_sto, col_wonum], ascending=[True, True])
        r4_data, r4_pending_data = [], []
        r4_mj_wa_header = f"Berikut list order MJ HI untuk status yang belum COMPWORK/WORKFAIL ya bang *{waktu_sekarang}*\n\n"
        
        if col_manja in df_r4_base.columns:
            df_r4 = df_r4_base[(df_r4_base[col_manja] == hari_ini_data) & (~df_r4_base['STATUS'].isin(['COMPWORK','WORKFAIL','CANCLWORK']))]
            for _, r in df_r4.dropna(subset=[col_sto, col_wonum]).iterrows():
                st, wo, status = str(r[col_sto]), str(r[col_wonum]), str(r['STATUS'])
                r4_data.append({"STO": st, "WONUM": wo, "STATUS": status, "FORMAT_WA": f"• {st} | {wo} | {status}"})

        r4_pending_wa = f"ORDER PENDING HI & RNA, KONFIRMASI ULANG, WAJIB MENJADI MODAL PS HI *{waktu_sekarang}*\n\n"
        col_memo = 'ENGINEERMEMO_AKHIR' if 'ENGINEERMEMO_AKHIR' in df_r4_base.columns else ('MEMO' if 'MEMO' in df_r4_base.columns else 'KETERANGAN')
        
        for _, r in df_r4_base.iterrows():
            st_val = str(r.get('STATUS', '')).strip().upper()
            err_val = str(r.get(col_err, '')).strip().upper()
            sub_val = str(r.get(col_sub, '')).strip().upper()
            
            memo_raw = str(r.get(col_memo, 'Tidak ada deskripsi memo.'))
            memo_val = memo_raw.strip().upper()
            all_text = f"{err_val} {sub_val} {memo_val}"
            
            sd_obj = r.get(col_sd)
            is_valid_date = pd.notnull(sd_obj)
            
            if st_val == 'WORKFAIL':
                is_kicked = False
                
                if tunggu_konfirm_pattern.search(memo_val):
                    is_kicked = True
                    
                if not is_kicked:
                    explicit_dates = re.findall(r'\b\d{1,2}[/\-]\d{1,2}[/\-]\d{2,4}\b', memo_val)
                    text_dates = re.findall(r'\b\d{1,2}\s+(?:JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER|JAN|FEB|MAR|APR|MEI|JUN|JUL|AGU|SEP|OKT|NOV|DES)\b', memo_val)
                    all_explicit_dates = explicit_dates + text_dates
                    
                    if all_explicit_dates:
                        has_today_in_explicit = False
                        for ed in all_explicit_dates:
                            if today_date_pattern.search(ed):
                                has_today_in_explicit = True
                                break
                        if not has_today_in_explicit:
                            is_kicked = True
                    else:
                        has_any_day = bool(re.search(r'\b(SENIN|SELASA|RABU|KAMIS|JUMAT|SABTU|MINGGU)\b', memo_val))
                        has_today_day = bool(re.search(fr'\b{hari_ini_nama}\b', memo_val))
                        has_hari_ini = bool(re.search(r'\bHARI INI\b', memo_val))
                        
                        is_besok = 'BESOK' in memo_val
                        diupdate_kemarin = (is_valid_date and sd_obj.date() == kemarin_data)
                        
                        if is_besok and not diupdate_kemarin and not has_hari_ini:
                            is_kicked = True
                        elif 'LUSA' in memo_val and not has_hari_ini:
                            is_kicked = True
                        elif has_any_day and not has_today_day and not has_hari_ini and not is_besok:
                            is_kicked = True
                            
                if is_kicked:
                    continue

                is_target = False
                simplified_text = ""
                
                if sub_val == 'RNA' or re.search(r'\bRNA\b', all_text):
                    if is_valid_date and (tiga_hari_lalu <= sd_obj.date() <= hari_ini_data):
                        is_target = True
                        simplified_text = "RNA, TOLONG KONFIRM ULANG"

                elif 'PENDING' in all_text or sub_val == 'PENDING':
                    is_target = True
                    
                    if 'SIANG/SORE' in memo_val or ('SIANG' in memo_val and 'SORE' in memo_val):
                        simplified_text = "MANJA HI, SIANG/SORE"
                    elif 'SIANG' in memo_val and not re.search(r'JAM\s*\d+', memo_val):
                        simplified_text = "MANJA HI, SIANG"
                    elif 'SORE' in memo_val and not re.search(r'JAM\s*\d+', memo_val):
                        simplified_text = "MANJA HI, SORE"
                    else:
                        jam_match = re.search(r'JAM\s*(\d+)', memo_val)
                        jam_slash_match = re.search(r'JAM\s*(\d+)/(\d+)', memo_val)
                        
                        if jam_slash_match:
                            simplified_text = f"MANJA HI, JAM {jam_slash_match.group(1)}/{jam_slash_match.group(2)}"
                        elif jam_match:
                            jam_num = int(jam_match.group(1))
                            if 'SIANG' in memo_val or 'SORE' in memo_val or 'MALAM' in memo_val:
                                if jam_num < 12: jam_num += 12
                            simplified_text = f"MANJA HI, JAM {jam_num}"
                        else:
                            simplified_text = "MANJA HI"
                            
                if is_target:
                    st_p, wo_p = str(r.get(col_sto, '')), str(r.get(col_wonum, ''))
                    r4_pending_data.append({"STO": st_p, "WONUM": wo_p, "STATUS": st_val, "MEMO_ASLI": memo_raw, "SIMPLIFIED": simplified_text})
                    r4_pending_wa += f"• {st_p} | {wo_p} | {st_val} | {simplified_text}\n"

        # ====== CHART DATA ======
        chart_trends_pack = {"ALL": {"re": [0]*len(list_jam), "ps": [0]*len(list_jam), "cum_ps": [0]*len(list_jam)}}
        for s in all_stos: chart_trends_pack[s] = {"re": [0]*len(list_jam), "ps": [0]*len(list_jam), "cum_ps": [0]*len(list_jam)}
        for idx, j in enumerate(list_jam):
            chart_trends_pack["ALL"]["re"][idx] = sum(1 for _, row in df_re.iterrows() if row['JAM_RE'] == j)
            chart_trends_pack["ALL"]["ps"][idx] = sum(1 for _, row in df_ps.iterrows() if row['JAM_PS'] == j)
            for s in all_stos:
                chart_trends_pack[s]["re"][idx] = sum(1 for _, row in df_re.iterrows() if row['JAM_RE'] == j and row[col_sto] == s)
                chart_trends_pack[s]["ps"][idx] = sum(1 for _, row in df_ps.iterrows() if row['JAM_PS'] == j and row[col_sto] == s)
        for k in chart_trends_pack.keys():
            run_tot = 0
            for i in range(len(list_jam)):
                run_tot += chart_trends_pack[k]["ps"][i]
                chart_trends_pack[k]["cum_ps"][i] = run_tot
        
        # LOGIKA PERCANTIK INSIGHT
        best_sto_text = f"{sorted_r1[0]['STO']} ({sorted_r1[0]['ACHIEV']})" if sorted_r1 else "-"
        worst_sto_text = f"{sorted_r1[-1]['STO']} ({sorted_r1[-1]['ACHIEV']})" if sorted_r1 else "-"

        return {
            "status": "success", "timestamp": waktu_sekarang, "stos": all_stos, "r3_stos": all_stos, "jam_kerja": list_jam,
            "available_dates": list_tanggal_unik, "available_datels": list_datel_unik,
            "active_start": active_start, "active_end": active_end, "active_datels": active_datels,
            "r1": r1_data, "r2": r2_data, "r3_wa": wa_text, "r3_pivot": r3_pivot_data, 
            "r4": r4_data, "r4_mj_wa_header": r4_mj_wa_header, "r4_pending": r4_pending_data, "r4_pending_wa": r4_pending_wa, 
            "chart_data": {"labels": [f"{j:02d}:00" for j in list_jam], "trends": chart_trends_pack},
            "insights": {"total_achiev": grand_achiev_pct, "best_sto": best_sto_text, "worst_sto": worst_sto_text, "most_kendala": most_kendala, "top_5": top_5, "bottom_5": bottom_5}
        }
    except Exception as e: 
        return {"status": "error", "message": str(e)}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="127.0.0.1", port=8000, reload=True)