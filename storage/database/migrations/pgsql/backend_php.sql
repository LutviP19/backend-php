--
-- PostgreSQL database dump
--

\restrict d9BHQkORxvzdmyb9ZPPCxBKiLr7CyNGmFlcMofLNljoyNE1ApKf0ciV5nLBEanj

-- Dumped from database version 16.14
-- Dumped by pg_dump version 18.4 (Ubuntu 18.4-0ubuntu0.26.04.1)

-- Started on 2026-08-15 20:07:14 WIB

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 4 (class 2615 OID 2200)
-- Name: public; Type: SCHEMA; Schema: -; Owner: pg_database_owner
--

CREATE SCHEMA public;


ALTER SCHEMA public OWNER TO pg_database_owner;

--
-- TOC entry 3647 (class 0 OID 0)
-- Dependencies: 4
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: pg_database_owner
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 219 (class 1259 OID 16422)
-- Name: activities; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.activities (
    id bigint NOT NULL,
    category_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    member character varying(255) NOT NULL,
    "time" time without time zone NOT NULL,
    status backend_php.activities_status NOT NULL,
    icon character varying(50) DEFAULT NULL::character varying,
    color character varying(30) DEFAULT NULL::character varying,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.activities OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 16421)
-- Name: activities_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activities_id_seq OWNER TO postgres;

--
-- TOC entry 3648 (class 0 OID 0)
-- Dependencies: 218
-- Name: activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.activities_id_seq OWNED BY public.activities.id;


--
-- TOC entry 223 (class 1259 OID 16441)
-- Name: asset_categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_categories (
    id bigint NOT NULL,
    category_name character varying(100) NOT NULL,
    slug character varying(50) NOT NULL
);


ALTER TABLE public.asset_categories OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 16440)
-- Name: asset_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_categories_id_seq OWNER TO postgres;

--
-- TOC entry 3649 (class 0 OID 0)
-- Dependencies: 222
-- Name: asset_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_categories_id_seq OWNED BY public.asset_categories.id;


--
-- TOC entry 225 (class 1259 OID 16446)
-- Name: asset_maintenance_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_maintenance_logs (
    id bigint NOT NULL,
    asset_id bigint NOT NULL,
    maintenance_date date NOT NULL,
    task character varying(255) NOT NULL,
    status backend_php.asset_maintenance_logs_status DEFAULT 'Selesai'::backend_php.asset_maintenance_logs_status,
    technician_name character varying(100) DEFAULT NULL::character varying,
    cost numeric(12,2) DEFAULT 0.00,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone
);


ALTER TABLE public.asset_maintenance_logs OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 16445)
-- Name: asset_maintenance_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_maintenance_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_maintenance_logs_id_seq OWNER TO postgres;

--
-- TOC entry 3650 (class 0 OID 0)
-- Dependencies: 224
-- Name: asset_maintenance_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_maintenance_logs_id_seq OWNED BY public.asset_maintenance_logs.id;


--
-- TOC entry 221 (class 1259 OID 16432)
-- Name: assets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.assets (
    id bigint NOT NULL,
    asset_id character varying(20) NOT NULL,
    category_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    status backend_php.assets_status DEFAULT 'ready'::backend_php.assets_status,
    health smallint,
    icon character varying(50) DEFAULT 'fa-box'::character varying,
    color character varying(30) DEFAULT 'slate'::character varying,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone
);


ALTER TABLE public.assets OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 16431)
-- Name: assets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.assets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assets_id_seq OWNER TO postgres;

--
-- TOC entry 3651 (class 0 OID 0)
-- Dependencies: 220
-- Name: assets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.assets_id_seq OWNED BY public.assets.id;


--
-- TOC entry 227 (class 1259 OID 16455)
-- Name: categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    slug character varying(50) NOT NULL,
    display_name character varying(100) NOT NULL,
    default_icon character varying(50) DEFAULT 'fa-circle'::character varying,
    default_color character varying(30) DEFAULT 'slate'::character varying
);


ALTER TABLE public.categories OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 16454)
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categories_id_seq OWNER TO postgres;

--
-- TOC entry 3652 (class 0 OID 0)
-- Dependencies: 226
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- TOC entry 229 (class 1259 OID 16462)
-- Name: fcm_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fcm_tokens (
    id bigint NOT NULL,
    user_id bigint,
    user_type character varying(30) DEFAULT NULL::character varying,
    token character varying(255) NOT NULL,
    token_expiry timestamp with time zone,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone
);


ALTER TABLE public.fcm_tokens OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 16461)
-- Name: fcm_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fcm_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.fcm_tokens_id_seq OWNER TO postgres;

--
-- TOC entry 3653 (class 0 OID 0)
-- Dependencies: 228
-- Name: fcm_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fcm_tokens_id_seq OWNED BY public.fcm_tokens.id;


--
-- TOC entry 230 (class 1259 OID 16468)
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp with time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 16478)
-- Name: permission_role; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permission_role (
    permission_id numeric NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.permission_role OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 16474)
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 16473)
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO postgres;

--
-- TOC entry 3654 (class 0 OID 0)
-- Dependencies: 231
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- TOC entry 235 (class 1259 OID 16484)
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id numeric NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp with time zone,
    expires_at timestamp with time zone,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 16483)
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- TOC entry 3655 (class 0 OID 0)
-- Dependencies: 234
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- TOC entry 237 (class 1259 OID 16491)
-- Name: products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    nama character varying(255) NOT NULL,
    kategori backend_php.products_kategori NOT NULL,
    stok bigint DEFAULT '0'::bigint,
    harga numeric(15,2) DEFAULT 0.00,
    status_kritis boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone
);


ALTER TABLE public.products OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 16490)
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.products_id_seq OWNER TO postgres;

--
-- TOC entry 3656 (class 0 OID 0)
-- Dependencies: 236
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- TOC entry 240 (class 1259 OID 16507)
-- Name: role_users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_users (
    user_id numeric NOT NULL,
    role_id bigint NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.role_users OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 16500)
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    slug character varying(255) DEFAULT ''::character varying,
    name character varying(255) NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 16499)
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- TOC entry 3657 (class 0 OID 0)
-- Dependencies: 238
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- TOC entry 242 (class 1259 OID 16513)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    ulid character(26) DEFAULT NULL::bpchar,
    name character varying(255) NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    email_verified_at timestamp with time zone,
    password character varying(255) NOT NULL,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp with time zone,
    client_token character varying(100) DEFAULT NULL::character varying,
    remember_token character varying(100) DEFAULT NULL::character varying,
    current_team_id numeric,
    profile_photo_path character varying(2048) DEFAULT NULL::character varying,
    first_name character varying(100) DEFAULT NULL::character varying,
    last_name character varying(100) DEFAULT NULL::character varying,
    phone character varying(30) DEFAULT NULL::character varying,
    address_line1 character varying(150) DEFAULT NULL::character varying,
    address_line2 character varying(200) DEFAULT NULL::character varying,
    city character varying(100) DEFAULT NULL::character varying,
    default_url character varying(255) DEFAULT NULL::character varying,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    deleted_at timestamp with time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 16512)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 3658 (class 0 OID 0)
-- Dependencies: 241
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 3378 (class 2604 OID 16425)
-- Name: activities id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activities ALTER COLUMN id SET DEFAULT nextval('public.activities_id_seq'::regclass);


--
-- TOC entry 3387 (class 2604 OID 16444)
-- Name: asset_categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_categories ALTER COLUMN id SET DEFAULT nextval('public.asset_categories_id_seq'::regclass);


--
-- TOC entry 3388 (class 2604 OID 16449)
-- Name: asset_maintenance_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_maintenance_logs ALTER COLUMN id SET DEFAULT nextval('public.asset_maintenance_logs_id_seq'::regclass);


--
-- TOC entry 3382 (class 2604 OID 16435)
-- Name: assets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets ALTER COLUMN id SET DEFAULT nextval('public.assets_id_seq'::regclass);


--
-- TOC entry 3393 (class 2604 OID 16458)
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- TOC entry 3396 (class 2604 OID 16465)
-- Name: fcm_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fcm_tokens ALTER COLUMN id SET DEFAULT nextval('public.fcm_tokens_id_seq'::regclass);


--
-- TOC entry 3399 (class 2604 OID 16477)
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- TOC entry 3400 (class 2604 OID 16487)
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- TOC entry 3401 (class 2604 OID 16494)
-- Name: products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- TOC entry 3406 (class 2604 OID 16503)
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- TOC entry 3408 (class 2604 OID 16516)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 3618 (class 0 OID 16422)
-- Dependencies: 219
-- Data for Name: activities; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.activities (id, category_id, title, member, "time", status, icon, color, created_at) FROM stdin;
1	1	Sewa Traktor Kubota L4400	Sukirman Harjo	08:00:00	Selesai	fa-tractor	emerald	2026-01-07 19:48:00+00
2	2	Simpanan Pokok Anggota	Siti Aminah	08:15:00	Selesai	fa-wallet	indigo	2026-01-07 19:48:00+00
3	3	Pupuk Urea Subsidi (50kg)	Gudang Utama B	08:45:00	Proses	fa-box	amber	2026-01-07 19:48:00+00
4	2	Angsuran Kredit Mikro	Budi Santoso	09:00:00	Selesai	fa-hand-holding-dollar	indigo	2026-01-07 19:48:00+00
5	1	Sewa Excavator Mini	PT. Maju Mundur	09:20:00	Selesai	fa-truck-pickup	emerald	2026-01-07 19:48:00+00
6	3	Bibit Padi Inpari 32	Kelompok Tani Sejati	09:45:00	Selesai	fa-seedling	emerald	2026-01-07 19:48:00+00
7	2	Pinjaman Darurat	Hendra Setiawan	10:10:00	Proses	fa-file-invoice-dollar	amber	2026-01-07 19:48:00+00
8	3	Pestisida Organik (10L)	Gudang Cabang C	10:30:00	Selesai	fa-flask	rose	2026-01-07 19:48:00+00
9	1	Sewa Drone Pertanian	Agus Kuncoro	11:00:00	Selesai	fa-plane-up	sky	2026-01-07 19:48:00+00
10	2	Simpanan Wajib Bulanan	Dewi Sartika	11:20:00	Selesai	fa-piggy-bank	indigo	2026-01-07 19:48:00+00
11	3	Restock Alat Cangkul (10 unit)	Gudang Pusat	13:00:00	Selesai	fa-hammer	slate	2026-01-07 19:48:00+00
12	1	Sewa Harvester Padi	H. Mulyadi	13:30:00	Proses	fa-gears	amber	2026-01-07 19:48:00+00
13	2	Penarikan Simpanan Sukarela	Rina Wijaya	14:00:00	Selesai	fa-money-bill-transfer	rose	2026-01-07 19:48:00+00
14	3	Bantuan Pupuk NPK	Kecamatan Makmur	14:20:00	Selesai	fa-leaf	emerald	2026-01-07 19:48:00+00
15	1	Sewa Pompa Air Irigasi	Tarno Sudirjo	14:45:00	Selesai	fa-faucet-drip	blue	2026-01-07 19:48:00+00
16	2	Pembayaran Deviden Anggota	Koperasi Pusat	15:10:00	Selesai	fa-percent	indigo	2026-01-07 19:48:00+00
17	3	Stok Pakan Ternak (100kg)	Peternakan Jaya	15:30:00	Selesai	fa-wheat-awn	amber	2026-01-07 19:48:00+00
18	1	Sewa Truk Engkel (Logistik)	Anton Kurniawan	16:00:00	Proses	fa-truck-moving	amber	2026-01-07 19:48:00+00
19	2	Simpanan Berjangka (Deposito)	Lestari Indah	16:20:00	Selesai	fa-vault	violet	2026-01-07 19:48:00+00
20	3	Alat Semprot Elektrik	Gudang Cabang A	16:45:00	Selesai	fa-spray-can-sparkles	emerald	2026-01-07 19:48:00+00
\.


--
-- TOC entry 3622 (class 0 OID 16441)
-- Dependencies: 223
-- Data for Name: asset_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_categories (id, category_name, slug) FROM stdin;
1	Alat Berat	heavy-equipment
2	Teknologi	technology
3	Pendukung	support
4	Peralatan Gudang	warehouse
5	Logistik	logistics
\.


--
-- TOC entry 3624 (class 0 OID 16446)
-- Dependencies: 225
-- Data for Name: asset_maintenance_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_maintenance_logs (id, asset_id, maintenance_date, task, status, technician_name, cost, created_at, updated_at) FROM stdin;
1	1	2025-11-05	Ganti Oli Mesin & Filter	Selesai	Budi Santoso	450000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
2	1	2025-11-20	Pengecekan Sistem Hidrolik	Selesai	Agus Prayogo	250000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
3	1	2025-12-05	Kalibrasi Sensor Teknologi	Selesai	Dani Tech	1200000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
4	1	2025-12-28	Penggantian Ban Belakang	Selesai	Budi Santoso	3500000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
5	1	2026-01-05	Pemeriksaan Rutin Mingguan	Selesai	Agus Prayogo	200000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
6	2	2025-10-15	Servis Berkala 500 Jam	Selesai	Eko Wijaya	2800000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
7	2	2025-11-10	Ganti Aki N70	Selesai	Budi Santoso	145000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
8	2	2025-12-01	Perbaikan Jalur Kabel Utama	Selesai	Dani Tech	600000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
9	2	2025-12-20	Penambahan Grease/Gemuk	Selesai	Agus Prayogo	150000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
10	2	2026-01-07	Pengecekan Kebocoran Bahan Bakar	Selesai	Eko Wijaya	100000.00	2026-01-08 13:52:31+00	2026-01-08 13:52:31+00
11	5	2025-11-05	Ganti Oli Mesin & Filter	Selesai	Budi Santoso	450000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
12	5	2025-11-20	Pengecekan Sistem Hidrolik	Selesai	Agus Prayogo	250000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
13	5	2025-12-05	Kalibrasi Sensor Teknologi	Selesai	Dani Tech	1200000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
14	5	2025-12-28	Penggantian Ban Belakang	Selesai	Budi Santoso	3500000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
15	5	2026-01-05	Pemeriksaan Rutin Mingguan	Selesai	Agus Prayogo	200000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
16	6	2025-10-15	Servis Berkala 500 Jam	Selesai	Eko Wijaya	2800000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
17	6	2025-11-10	Ganti Aki N70	Selesai	Budi Santoso	145000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
18	6	2025-12-01	Perbaikan Jalur Kabel Utama	Selesai	Dani Tech	600000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
19	6	2025-12-20	Penambahan Grease/Gemuk	Selesai	Agus Prayogo	150000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
20	6	2026-01-07	Pengecekan Kebocoran Bahan Bakar	Selesai	Eko Wijaya	100000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
21	19	2025-10-15	Servis Berkala 500 Jam	Selesai	Eko Wijaya	2800000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
22	19	2025-11-10	Ganti Aki N70	Selesai	Budi Santoso	145000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
23	19	2025-12-01	Perbaikan Jalur Kabel Utama	Selesai	Dani Tech	600000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
24	19	2025-12-20	Penambahan Grease/Gemuk	Selesai	Agus Prayogo	150000.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
25	19	2026-01-07	Pengecekan Kebocoran Bahan Bakar	Proses	Eko Wijaya	0.00	2026-01-08 13:56:11+00	2026-01-08 13:56:11+00
\.


--
-- TOC entry 3620 (class 0 OID 16432)
-- Dependencies: 221
-- Data for Name: assets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.assets (id, asset_id, category_id, name, status, health, icon, color, created_at, updated_at) FROM stdin;
1	DRN-01	2	DJI Agras T40 (Sprayer)	ready	75	fa-plane-up	sky	2026-01-07 20:39:47+00	2026-01-12 12:40:08+00
2	DRN-02	2	DJI Mavic 3 (Mapping)	working	98	fa-helicopter	emerald	2026-01-07 20:39:47+00	2026-01-12 16:47:04+00
3	DRN-03	2	Drone Sprayer V2	working	86	fa-plane	rose	2026-01-07 20:39:47+00	2026-01-08 17:49:57+00
4	EXC-01	1	Excavator Mini Hitachi	ready	85	fa-truck-pickup	cyan	2026-01-07 20:39:47+00	2026-01-13 13:23:54+00
5	EXC-02	1	Excavator PC200	working	80	fa-truck-monster	emerald	2026-01-07 20:39:47+00	2026-01-08 17:39:02+00
6	FOR-01	4	Forklift Toyota 3-Ton Edit2	maintenance	50	fa-dolly	orange	2026-01-07 20:39:47+00	2026-01-13 13:42:28+00
7	FOR-02	4	Forklift Toyota 3-Ton	ready	82	fa-dolly	emerald	2026-01-07 20:39:47+00	2026-01-07 20:39:47+00
8	GEN-01	4	Genset Cummins 50KVA	ready	95	fa-plug	slate	2026-01-07 20:39:47+00	2026-01-18 18:43:52+00
9	HVS-01	1	Harvester Padi Yanmar	ready	78	fa-gear	indigo	2026-01-07 20:39:47+00	2026-01-07 20:39:47+00
10	KBT-01	1	Traktor Kubota L4400	ready	85	fa-tractor	emerald	2026-01-07 20:39:47+00	2026-01-07 20:39:47+00
11	KBT-02	1	Traktor Kubota L4400	maintenance	32	fa-tractor	rose	2026-01-07 20:39:47+00	2026-01-07 20:39:47+00
12	KBT-03	1	Traktor Kubota L4400	ready	100	fa-tractor	emerald	2026-01-08 12:44:33+00	2026-01-13 12:55:55+00
13	KBT-04	1	Traktor Kubota L4400	working	74	fa-tractor	orange	2026-01-08 12:46:33+00	2026-01-18 19:48:09+00
14	KBT-05	1	Traktor Kubota L4400	ready	100	fa-tractor	emerald	2026-01-08 12:48:29+00	2026-01-08 12:48:29+00
15	KBT-06	1	Traktor Kubota L4400	working	100	fa-tractor	emerald	2026-01-08 12:50:46+00	2026-01-12 16:46:54+00
16	KBT-07	1	Traktor Kubota L4400	ready	100	fa-tractor	emerald	2026-01-08 12:51:54+00	2026-01-08 12:51:54+00
17	KBT-08	1	Traktor Kubota L4400	working	100	fa-tractor	emerald	2026-01-08 12:53:02+00	2026-01-08 17:48:33+00
18	PMP-01	3	Pompa Irigasi Diesel	ready	90	fa-faucet-drip	blue	2026-01-07 20:39:47+00	2026-01-07 20:39:47+00
19	PMP-02	3	Pompa Irigasi Diesel	ready	85	fa-faucet-drip	amber	2026-01-07 20:39:47+00	2026-01-13 12:57:12+00
20	TRK-01	3	Truk Engkel Logistik	working	76	fa-truck	amber	2026-01-07 20:39:47+00	2026-01-08 17:43:44+00
21	TRK-02	3	Truk Mitsubishi Canter	ready	88	fa-truck-moving	emerald	2026-01-07 20:39:47+00	2026-01-07 20:39:47+00
22	KBT-011	1	Traktor Kubota L4400	working	100	fa-tractor	emerald	2026-01-08 13:02:42+00	2026-01-13 12:56:14+00
23	KBT-012	1	Traktor Kubota L4400	ready	80	fa-tractor	emerald	2026-01-08 13:03:15+00	2026-01-13 19:18:01+00
24	KBT-013	1	Traktor Kubota L4400	working	100	fa-tractor	emerald	2026-01-08 13:03:41+00	2026-01-08 17:48:23+00
25	KBT-014	1	Traktor Kubota L4400	ready	85	fa-tractor	emerald	2026-01-08 13:29:54+00	2026-01-18 19:47:34+00
26	DRN-011	2	DJI Agras T40 (Sprayer)	working	85	fa-tractor	blue	2026-01-08 13:34:27+00	2026-01-08 16:58:22+00
27	DRN-012	2	DJI Agras T40 (Sprayer)	ready	100	fa-plane-up	blue	2026-01-08 13:35:35+00	2026-01-08 19:07:52+00
28	KBT-030	1	Traktor Kubota L4400	working	100	fa-tractor	emerald	2026-01-08 13:38:08+00	2026-01-08 17:37:58+00
29	PCKUP-1	5	Pickup L300	working	100	fa-truck-pickup	emerald	2026-01-08 14:08:37+00	2026-01-08 17:11:43+00
30	PCKUP-2	5	Pickup L300 Super 2025	working	88	fa-truck-pickup	emerald	2026-01-08 14:15:14+00	2026-01-08 16:47:50+00
31	DRN-04	2	DJI Mavic 3 (Mapping)	working	100	fa-helicopter	sky	2026-01-08 14:59:23+00	2026-01-08 16:48:08+00
32	DRN-05	2	Drone Pemindai Lahan	ready	100	fa-helicopter	emerald	2026-01-18 19:57:21+00	2026-01-18 19:58:29+00
33	DRN-06	2	Drone Pemindai Lahan	ready	100	fa-helicopter	emerald	2026-01-18 20:03:35+00	2026-01-18 20:03:35+00
34	DRN-07	2	DJI Agras T40 (Sprayer)	ready	100	fa-plane-up	indigo	2026-01-18 20:16:44+00	2026-01-18 20:16:44+00
35	DRN-08	2	DJI Mavic 3 (Mapping)	ready	100	fa-plane-up	rose	2026-01-18 20:18:42+00	2026-01-18 20:18:42+00
\.


--
-- TOC entry 3626 (class 0 OID 16455)
-- Dependencies: 227
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categories (id, slug, display_name, default_icon, default_color) FROM stdin;
1	assets	Aset & Alat	fa-tractor	emerald
2	finance	Keuangan	fa-wallet	indigo
3	inventory	Inventaris	fa-box	amber
\.


--
-- TOC entry 3628 (class 0 OID 16462)
-- Dependencies: 229
-- Data for Name: fcm_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fcm_tokens (id, user_id, user_type, token, token_expiry, created_at, updated_at) FROM stdin;
4	1	userx	dhRNFyAG6yebR4-AlO8i5m:APA91bEzd7pFngurkrJNzGLEM6yJdZE3210FGvT-FXgz5ue_zR_cy9ptCJJkinb7osKVJy5eH011Z4-CmVYZ2P5dn752nTtxCiTjq_mmwra1urY3f3AYs38	2025-12-11 12:36:41+00	2025-12-08 12:36:41+00	\N
5	1	userx	dhRNFyAG6yebR4-AlO8i5m:APA91bE5nd2-gVElgB6eBEhHqIGg95ymmfE8YbOPxeM1VFuimvzBTjdrds2aLhukptH0h-_ah7araGmhKMV3PGMTL4fmPrgr8LD_Q9Ek1icidJ-Fy1LZLlw	2025-12-11 15:39:33+00	2025-12-08 15:39:33+00	\N
11	1	userx	cr4Xa-B8iA9L4eaeILkwYw:APA91bH3GTS-wyCQs1O3YTmTMLfa4o6kutvCTOHYa_yjCkfJP48GwYApZrj1MB9zJqhK0qCGGVFgfjpyP7QF7hnKmQgyxNEt1d0t_KOHBLSdTgli3TwzGNg	2026-07-01 10:40:31+00	2026-06-28 10:40:31+00	\N
15	1	userx	fTpOTHt4FDw_ic35ssMis8:APA91bEI1ZJtlLcAd7vq5Np73bDhDmHueRzNCGu8-H_Tu25ZsrIFoN6O36u2K63ESQsrIZ5Q-gG_MyxaVYgl9A3TkJF6sK_y_X-2_DFmmrZOpZC7rp4hC-8	2026-07-01 12:32:28+00	2026-06-28 12:32:28+00	\N
\.


--
-- TOC entry 3629 (class 0 OID 16468)
-- Dependencies: 230
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- TOC entry 3632 (class 0 OID 16478)
-- Dependencies: 233
-- Data for Name: permission_role; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permission_role (permission_id, role_id) FROM stdin;
1	1
2	1
3	1
4	1
5	1
6	1
7	1
8	1
9	1
10	1
11	1
12	1
13	1
14	1
15	1
16	1
17	1
18	1
19	1
20	1
21	1
22	1
23	1
24	1
25	1
26	1
27	1
28	1
29	1
30	1
\.


--
-- TOC entry 3631 (class 0 OID 16474)
-- Dependencies: 232
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permissions (id, name, created_at, updated_at) FROM stdin;
1	super.view	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
2	super.create	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
3	super.update	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
4	super.delete	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
5	super.restore	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
6	administrator.view	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
7	administrator.create	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
8	administrator.update	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
9	administrator.delete	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
10	administrator.restore	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
11	admin.view	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
12	admin.create	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
13	admin.update	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
14	admin.delete	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
15	admin.restore	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
16	user.view	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
17	user.create	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
18	user.update	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
19	user.delete	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
20	user.restore	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
21	staff.view	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
22	staff.create	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
23	staff.update	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
24	staff.delete	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
25	staff.restore	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
26	client.view	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
27	client.create	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
28	client.update	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
29	client.delete	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
30	client.restore	2025-09-30 17:24:05+00	2025-09-30 17:24:05+00
31	order.view	2025-09-30 17:32:58+00	2025-09-30 17:32:58+00
32	order.create	2025-09-30 17:32:58+00	2025-09-30 17:32:58+00
33	order.update	2025-09-30 17:32:58+00	2025-09-30 17:32:58+00
34	order.delete	2025-09-30 17:32:58+00	2025-09-30 17:32:58+00
35	order.restore	2025-09-30 17:32:58+00	2025-09-30 17:32:58+00
\.


--
-- TOC entry 3634 (class 0 OID 16484)
-- Dependencies: 235
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 3636 (class 0 OID 16491)
-- Dependencies: 237
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.products (id, nama, kategori, stok, harga, status_kritis, created_at, updated_at) FROM stdin;
1	Pupuk NPK Edit	pupuk	100	150000.00	t	2026-01-05 07:22:54+00	2026-01-05 07:23:04+00
2	Pupuk Cair EM4 Kuning	pupuk	150	12000.00	t	2026-01-01 01:04:16+00	2026-01-12 18:20:21+00
3	Pupuk Cair EM4 Merah	pupuk	100	18000.00	t	2026-01-01 01:04:39+00	2026-01-01 17:21:48+00
4	Pupuk Mutiara	pupuk	300	10000.00	t	2026-01-01 01:50:47+00	2026-01-07 12:59:22+00
5	Benih Padi Super A+	benih	28	250000.00	t	2026-01-01 01:53:11+00	2026-01-12 17:39:31+00
6	Pupuk Urea Nitrea 50kg	pupuk	5	250000.00	t	2026-01-01 01:53:50+00	2026-01-01 02:17:25+00
7	Benih Jagung Pioner P35	benih	15	115000.00	t	2026-01-01 01:54:50+00	2026-01-13 13:42:47+00
8	Rubigan 120 EC	pestisida	45	95000.00	t	2026-01-01 01:55:20+00	2026-01-07 12:47:37+00
9	Pupuk NPK Mutiara 16-16-16	pupuk	250	115000.00	t	2026-01-01 01:55:59+00	2026-01-07 12:46:20+00
10	Raydock 28 EC	pestisida	120	30000.00	t	2026-01-08 19:17:20+00	2026-01-12 16:49:00+00
11	Fenval 200 EC	pestisida	100	70000.00	t	2026-01-08 19:17:54+00	2026-01-08 19:17:54+00
12	Varitas 3 GR	pestisida	5	67000.00	t	2026-01-08 19:18:21+00	2026-01-08 19:22:03+00
13	Meteor 25 EC	pestisida	50	125000.00	t	2026-01-08 19:18:51+00	2026-01-18 18:43:42+00
14	Pupuk Cair EM4 Kuning	pupuk	150	12000.00	t	2026-01-01 01:04:16+00	2026-01-12 18:20:21+00
15	Pupuk Cair EM4 Merah	pupuk	100	18000.00	t	2026-01-01 01:04:39+00	2026-01-01 17:21:48+00
16	Pupuk Mutiara	pupuk	300	10000.00	t	2026-01-01 01:50:47+00	2026-01-07 12:59:22+00
17	Benih Padi Super A+	benih	28	250000.00	t	2026-01-01 01:53:11+00	2026-01-12 17:39:31+00
18	Pupuk Urea Nitrea 50kg	pupuk	5	250000.00	t	2026-01-01 01:53:50+00	2026-01-01 02:17:25+00
19	Benih Jagung Pioner P35	benih	15	115000.00	t	2026-01-01 01:54:50+00	2026-01-13 13:42:47+00
20	Rubigan 120 EC	pestisida	45	95000.00	t	2026-01-01 01:55:20+00	2026-01-07 12:47:37+00
21	Pupuk NPK Mutiara 16-16-16	pupuk	250	115000.00	t	2026-01-01 01:55:59+00	2026-01-07 12:46:20+00
22	Raydock 28 EC	pestisida	120	30000.00	t	2026-01-08 19:17:20+00	2026-01-12 16:49:00+00
23	Fenval 200 EC	pestisida	100	70000.00	t	2026-01-08 19:17:54+00	2026-01-08 19:17:54+00
24	Varitas 3 GR	pestisida	5	67000.00	t	2026-01-08 19:18:21+00	2026-01-08 19:22:03+00
25	Meteor 25 EC	pestisida	50	125000.00	t	2026-01-08 19:18:51+00	2026-01-18 18:43:42+00
\.


--
-- TOC entry 3639 (class 0 OID 16507)
-- Dependencies: 240
-- Data for Name: role_users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_users (user_id, role_id, created_at, updated_at) FROM stdin;
1	1	2025-09-30 16:48:38+00	2025-09-30 16:48:43+00
\.


--
-- TOC entry 3638 (class 0 OID 16500)
-- Dependencies: 239
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, slug, name, created_at, updated_at) FROM stdin;
1	super	Super	2025-09-30 16:47:46+00	2025-09-30 16:47:46+00
2	administrator	Administrator	2025-09-30 16:47:46+00	2025-09-30 16:47:46+00
3	admin	Admin	2025-09-30 16:47:46+00	2025-09-30 16:47:46+00
4	user	User	2025-09-30 16:47:46+00	2025-09-30 16:47:46+00
5	staff	Staff	2025-09-30 16:47:46+00	2025-09-30 16:47:46+00
\.


--
-- TOC entry 3641 (class 0 OID 16513)
-- Dependencies: 242
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, ulid, name, email, email_verified_at, password, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, client_token, remember_token, current_team_id, profile_photo_path, first_name, last_name, phone, address_line1, address_line2, city, default_url, status, created_at, updated_at, deleted_at) FROM stdin;
1	01JP9MA549R9NNVNGHTHJFTNXJ	Admin	admin@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	jHd4uFGBV0RPgxSW*TRI^b@IQZb#XKlQCHh#hAPlr8yXK7ELXr@I9$UMl^Dp%7tm	5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW	1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-14 10:44:03+00	2025-03-16 12:16:10+00	\N
2	01K58Z4P34ZHADC974APH89PQ3	Admin2	admin2@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	lCqGXYmoOnC$53JGddawFqSonNAK2fLVug.crPbqNkIK2KDEYPZkLvndrjcHD6YQ	4nHlxJ09Sg^dFB#0JwdS8kAmGagi7SiEj@cZ3H*f6LV8eejd%ksLSCMX#zQvYUTB	1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-15 14:47:21+00	2025-03-15 14:49:25+00	\N
3	01K58Z55E65CZCGMJJJSEMSMY0	Admin3	admin3@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	mgpiewufeMEVB6OA7s2Ca1vgNYPWTkdGFyaHfJxJ9n25jfGiELXKP8egsgJu		1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-15 15:20:29+00	2025-03-15 15:26:24+00	\N
4	01K58Z5J41YKCN31H9C5GAMFKA	Admin4	admin4@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	BRaX6JLNzt2uiGLAcGrk3q6qGevlSJOepuGmKqJlVFmOJYtmrS4Qdl3ahVNI		1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-15 15:28:07+00	2025-03-15 15:28:07+00	\N
5	01K58Z5Y1RBQCWQWASJRSE2JKQ	Admin5	admin5@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	beK3p9VzMG8Isi4yGoVsJZGqAO1wAFhMpCcnQKFYVbG53HCWITiR7J2Wibod		1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-15 15:30:55+00	2025-03-15 15:30:55+00	\N
6	01K58Z6C2KJKQ0QZ9M79S3MJ3S	Admin6	admin6@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW		1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-15 15:39:33+00	2025-03-15 15:39:33+00	\N
7	01K58Z6P2DJGDJGMH0WWFYBTG3	Admin7	admin7@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW		1	\N	\N	\N	\N	\N	\N	\N	\N	t	2025-03-15 15:41:15+00	2025-03-15 15:41:15+00	\N
8	01K58Z71Y4AYC1K42J1J4AEGC7	Admin8	admin8@example.com	\N	$2y$10$DNGjs3OU3BIvoqCDsxjiCO.VQJe45BO0bUo55LwnMV2ueJ0d6i0WK	\N	\N	\N	5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW		5	\N	\N	\N	\N	\N	\N	\N	\N	f	2025-03-15 16:14:31+00	2025-03-15 16:14:31+00	\N
\.


--
-- TOC entry 3659 (class 0 OID 0)
-- Dependencies: 218
-- Name: activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.activities_id_seq', 20, true);


--
-- TOC entry 3660 (class 0 OID 0)
-- Dependencies: 222
-- Name: asset_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_categories_id_seq', 5, true);


--
-- TOC entry 3661 (class 0 OID 0)
-- Dependencies: 224
-- Name: asset_maintenance_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_maintenance_logs_id_seq', 25, true);


--
-- TOC entry 3662 (class 0 OID 0)
-- Dependencies: 220
-- Name: assets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.assets_id_seq', 35, true);


--
-- TOC entry 3663 (class 0 OID 0)
-- Dependencies: 226
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categories_id_seq', 3, true);


--
-- TOC entry 3664 (class 0 OID 0)
-- Dependencies: 228
-- Name: fcm_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fcm_tokens_id_seq', 15, true);


--
-- TOC entry 3665 (class 0 OID 0)
-- Dependencies: 231
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permissions_id_seq', 35, true);


--
-- TOC entry 3666 (class 0 OID 0)
-- Dependencies: 234
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 1, true);


--
-- TOC entry 3667 (class 0 OID 0)
-- Dependencies: 236
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.products_id_seq', 25, true);


--
-- TOC entry 3668 (class 0 OID 0)
-- Dependencies: 238
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 5, true);


--
-- TOC entry 3669 (class 0 OID 0)
-- Dependencies: 241
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 8, true);


--
-- TOC entry 3424 (class 2606 OID 16571)
-- Name: activities idx_16422_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT idx_16422_primary PRIMARY KEY (id);


--
-- TOC entry 3428 (class 2606 OID 16566)
-- Name: assets idx_16432_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT idx_16432_primary PRIMARY KEY (id);


--
-- TOC entry 3431 (class 2606 OID 16572)
-- Name: asset_categories idx_16441_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_categories
    ADD CONSTRAINT idx_16441_primary PRIMARY KEY (id);


--
-- TOC entry 3435 (class 2606 OID 16568)
-- Name: asset_maintenance_logs idx_16446_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_maintenance_logs
    ADD CONSTRAINT idx_16446_primary PRIMARY KEY (id);


--
-- TOC entry 3438 (class 2606 OID 16577)
-- Name: categories idx_16455_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT idx_16455_primary PRIMARY KEY (id);


--
-- TOC entry 3440 (class 2606 OID 16574)
-- Name: fcm_tokens idx_16462_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fcm_tokens
    ADD CONSTRAINT idx_16462_primary PRIMARY KEY (id);


--
-- TOC entry 3443 (class 2606 OID 16579)
-- Name: password_reset_tokens idx_16468_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT idx_16468_primary PRIMARY KEY (email);


--
-- TOC entry 3445 (class 2606 OID 16567)
-- Name: permissions idx_16474_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT idx_16474_primary PRIMARY KEY (id);


--
-- TOC entry 3448 (class 2606 OID 16569)
-- Name: permission_role idx_16478_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permission_role
    ADD CONSTRAINT idx_16478_primary PRIMARY KEY (permission_id, role_id);


--
-- TOC entry 3452 (class 2606 OID 16578)
-- Name: personal_access_tokens idx_16484_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT idx_16484_primary PRIMARY KEY (id);


--
-- TOC entry 3456 (class 2606 OID 16570)
-- Name: products idx_16491_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT idx_16491_primary PRIMARY KEY (id);


--
-- TOC entry 3458 (class 2606 OID 16575)
-- Name: roles idx_16500_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT idx_16500_primary PRIMARY KEY (id);


--
-- TOC entry 3460 (class 2606 OID 16576)
-- Name: role_users idx_16507_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_users
    ADD CONSTRAINT idx_16507_primary PRIMARY KEY (user_id, role_id);


--
-- TOC entry 3463 (class 2606 OID 16573)
-- Name: users idx_16513_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT idx_16513_primary PRIMARY KEY (id);


--
-- TOC entry 3422 (class 1259 OID 16543)
-- Name: idx_16422_fk_activity_category; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16422_fk_activity_category ON public.activities USING btree (category_id);


--
-- TOC entry 3425 (class 1259 OID 16533)
-- Name: idx_16432_asset_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_16432_asset_id ON public.assets USING btree (asset_id);


--
-- TOC entry 3426 (class 1259 OID 16532)
-- Name: idx_16432_category_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16432_category_id ON public.assets USING btree (category_id);


--
-- TOC entry 3429 (class 1259 OID 16544)
-- Name: idx_16441_idx_category_name; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16441_idx_category_name ON public.asset_categories USING btree (category_name);


--
-- TOC entry 3432 (class 1259 OID 16548)
-- Name: idx_16441_slug; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_16441_slug ON public.asset_categories USING btree (slug);


--
-- TOC entry 3433 (class 1259 OID 16535)
-- Name: idx_16446_fk_logs_asset_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16446_fk_logs_asset_id ON public.asset_maintenance_logs USING btree (asset_id);


--
-- TOC entry 3436 (class 1259 OID 16555)
-- Name: idx_16455_idx_slug; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_16455_idx_slug ON public.categories USING btree (slug);


--
-- TOC entry 3441 (class 1259 OID 16550)
-- Name: idx_16462_uniq; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_16462_uniq ON public.fcm_tokens USING btree (token);


--
-- TOC entry 3446 (class 1259 OID 16540)
-- Name: idx_16478_permission_role_role_id_foreign; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16478_permission_role_role_id_foreign ON public.permission_role USING btree (role_id);


--
-- TOC entry 3449 (class 1259 OID 16558)
-- Name: idx_16484_personal_access_tokens_token_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_16484_personal_access_tokens_token_unique ON public.personal_access_tokens USING btree (token);


--
-- TOC entry 3450 (class 1259 OID 16556)
-- Name: idx_16484_personal_access_tokens_tokenable_type_tokenable_id_in; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16484_personal_access_tokens_tokenable_type_tokenable_id_in ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- TOC entry 3453 (class 1259 OID 16539)
-- Name: idx_16491_idx_kategori; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16491_idx_kategori ON public.products USING btree (kategori);


--
-- TOC entry 3454 (class 1259 OID 16542)
-- Name: idx_16491_idx_stok; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16491_idx_stok ON public.products USING btree (stok);


--
-- TOC entry 3461 (class 1259 OID 16553)
-- Name: idx_16507_role_user_role_id_foreign; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_16507_role_user_role_id_foreign ON public.role_users USING btree (role_id);


--
-- TOC entry 3464 (class 1259 OID 16549)
-- Name: idx_16513_users_email_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_16513_users_email_unique ON public.users USING btree (email);


--
-- TOC entry 3471 (class 2620 OID 16608)
-- Name: asset_maintenance_logs on_update_current_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON public.asset_maintenance_logs FOR EACH ROW EXECUTE FUNCTION backend_php.on_update_current_timestamp_asset_maintenance_logs();


--
-- TOC entry 3470 (class 2620 OID 16606)
-- Name: assets on_update_current_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON public.assets FOR EACH ROW EXECUTE FUNCTION backend_php.on_update_current_timestamp_assets();


--
-- TOC entry 3472 (class 2620 OID 16610)
-- Name: fcm_tokens on_update_current_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON public.fcm_tokens FOR EACH ROW EXECUTE FUNCTION backend_php.on_update_current_timestamp_fcm_tokens();


--
-- TOC entry 3473 (class 2620 OID 16612)
-- Name: products on_update_current_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON public.products FOR EACH ROW EXECUTE FUNCTION backend_php.on_update_current_timestamp_products();


--
-- TOC entry 3466 (class 2606 OID 16585)
-- Name: assets assets_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_ibfk_1 FOREIGN KEY (category_id) REFERENCES public.asset_categories(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- TOC entry 3465 (class 2606 OID 16580)
-- Name: activities fk_activity_category; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT fk_activity_category FOREIGN KEY (category_id) REFERENCES public.categories(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- TOC entry 3467 (class 2606 OID 16590)
-- Name: asset_maintenance_logs fk_logs_asset_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_maintenance_logs
    ADD CONSTRAINT fk_logs_asset_id FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3468 (class 2606 OID 16595)
-- Name: permission_role permission_role_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permission_role
    ADD CONSTRAINT permission_role_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 3469 (class 2606 OID 16600)
-- Name: role_users role_user_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_users
    ADD CONSTRAINT role_user_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON UPDATE CASCADE ON DELETE CASCADE;


-- Completed on 2026-08-15 20:07:14 WIB

--
-- PostgreSQL database dump complete
--

\unrestrict d9BHQkORxvzdmyb9ZPPCxBKiLr7CyNGmFlcMofLNljoyNE1ApKf0ciV5nLBEanj
