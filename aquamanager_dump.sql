--
-- PostgreSQL database dump
--

\restrict YRk5iMOjHng6F1q4HCbFcO0qfW39PaurOXrdYeOKir5ompbYQNJdPhdzlMUm1dO

-- Dumped from database version 16.14 (Debian 16.14-1.pgdg13+1)
-- Dumped by pg_dump version 16.13

-- Started on 2026-06-04 13:30:01 UTC

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 861 (class 1247 OID 16422)
-- Name: health_status_enum; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.health_status_enum AS ENUM (
    'Excellent',
    'Good',
    'Monitor',
    'Critical'
);


--
-- TOC entry 858 (class 1247 OID 16412)
-- Name: tank_status_enum; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tank_status_enum AS ENUM (
    'Healthy',
    'Attention',
    'Empty',
    'Quarantine'
);


--
-- TOC entry 849 (class 1247 OID 16386)
-- Name: user_role; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.user_role AS ENUM (
    'admin',
    'user',
    'pro_member'
);


--
-- TOC entry 855 (class 1247 OID 16406)
-- Name: water_type_enum; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.water_type_enum AS ENUM (
    'Freshwater',
    'Saltwater'
);


--
-- TOC entry 224 (class 1255 OID 16529)
-- Name: fn_validate_water_compatibility(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_validate_water_compatibility() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
v_tank_water_type water_type_enum;
    v_species_water_type water_type_enum;
BEGIN
SELECT water_type INTO v_tank_water_type FROM tanks WHERE id_tank = NEW.id_tank;
SELECT water_compatibility INTO v_species_water_type FROM species WHERE id_species = NEW.id_species;

IF v_tank_water_type != v_species_water_type THEN
        RAISE EXCEPTION 'Ecosystem mismatch: Attempted to add % species to a % tank!', v_species_water_type, v_tank_water_type;
END IF;
RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 221 (class 1259 OID 16506)
-- Name: installed_equipment; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.installed_equipment (
    id_equipment uuid DEFAULT gen_random_uuid() NOT NULL,
    id_tank uuid NOT NULL,
    name character varying(100) NOT NULL,
    type character varying(50) NOT NULL,
    status character varying(50) DEFAULT 'Active'::character varying,
    added_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 218 (class 1259 OID 16460)
-- Name: species; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.species (
    id_species uuid DEFAULT gen_random_uuid() NOT NULL,
    common_name character varying(100) NOT NULL,
    scientific_name character varying(150) NOT NULL,
    water_compatibility public.water_type_enum NOT NULL,
    ideal_ph_min numeric(3,1),
    ideal_ph_max numeric(3,1),
    ideal_temp_min numeric(4,1),
    ideal_temp_max numeric(4,1),
    image_path character varying(255) DEFAULT '/public/img/catalog/placeholder.png'::character varying
);


--
-- TOC entry 219 (class 1259 OID 16471)
-- Name: tank_livestock; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tank_livestock (
    id_livestock uuid DEFAULT gen_random_uuid() NOT NULL,
    id_tank uuid NOT NULL,
    id_species uuid NOT NULL,
    quantity integer NOT NULL,
    health public.health_status_enum DEFAULT 'Good'::public.health_status_enum,
    added_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT tank_livestock_quantity_check CHECK ((quantity > 0))
);


--
-- TOC entry 217 (class 1259 OID 16443)
-- Name: tanks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tanks (
    id_tank uuid DEFAULT gen_random_uuid() NOT NULL,
    id_user uuid NOT NULL,
    name character varying(100) NOT NULL,
    water_type public.water_type_enum NOT NULL,
    volume_liters integer NOT NULL,
    width_cm integer,
    height_cm integer,
    depth_cm integer,
    status public.tank_status_enum DEFAULT 'Empty'::public.tank_status_enum,
    installation_date date,
    image_path character varying(255) DEFAULT '/public/img/tanks/default-tank.png'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT tanks_volume_liters_check CHECK ((volume_liters > 0))
);


--
-- TOC entry 216 (class 1259 OID 16431)
-- Name: user_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_profiles (
    id_user uuid NOT NULL,
    full_name character varying(100),
    subscription_tier character varying(50) DEFAULT 'Standard'::character varying,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 215 (class 1259 OID 16393)
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id_user uuid DEFAULT gen_random_uuid() NOT NULL,
    email character varying(255) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role public.user_role DEFAULT 'user'::public.user_role,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 222 (class 1259 OID 16519)
-- Name: v_dashboard_summary; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_dashboard_summary AS
 SELECT t.id_user,
    t.id_tank,
    t.name AS tank_name,
    t.water_type,
    t.volume_liters,
    t.status,
    t.image_path,
    t.notes,
    COALESCE(sum(tl.quantity), (0)::bigint) AS total_livestock_count
   FROM (public.tanks t
     LEFT JOIN public.tank_livestock tl ON ((t.id_tank = tl.id_tank)))
  GROUP BY t.id_user, t.id_tank, t.name, t.water_type, t.volume_liters, t.status, t.image_path, t.notes;


--
-- TOC entry 223 (class 1259 OID 16524)
-- Name: v_tank_ecosystem_details; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_tank_ecosystem_details AS
 SELECT t.id_tank,
    s.common_name,
    s.scientific_name,
    tl.quantity,
    tl.health
   FROM ((public.tank_livestock tl
     JOIN public.tanks t ON ((tl.id_tank = t.id_tank)))
     JOIN public.species s ON ((tl.id_species = s.id_species)));


--
-- TOC entry 220 (class 1259 OID 16492)
-- Name: water_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.water_logs (
    id_log uuid DEFAULT gen_random_uuid() NOT NULL,
    id_tank uuid NOT NULL,
    ph_level numeric(3,1) NOT NULL,
    temperature numeric(4,1) NOT NULL,
    notes text,
    logged_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 3510 (class 0 OID 16506)
-- Dependencies: 221
-- Data for Name: installed_equipment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.installed_equipment (id_equipment, id_tank, name, type, status, added_at) FROM stdin;
77587b2d-862e-48c1-8314-d1ef278d7a88	851f049c-f7ae-46b5-9c34-bf1da5bb088a	Chihiros	Lighting	Active	2026-06-04 12:46:56.828815
\.


--
-- TOC entry 3507 (class 0 OID 16460)
-- Dependencies: 218
-- Data for Name: species; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.species (id_species, common_name, scientific_name, water_compatibility, ideal_ph_min, ideal_ph_max, ideal_temp_min, ideal_temp_max, image_path) FROM stdin;
282e5d3d-3226-401a-b5d5-2d17c7d65782	Coral Beauty Angelfish	Centropyge bispinosa	Saltwater	8.1	8.4	24.0	28.0	/public/img/catalog/species_6a2172ec78028.jpg
fbed6d84-b505-40cb-b118-e8e4e799b5d2	Fancy Guppy	Poecilia reticulata	Freshwater	6.8	7.8	22.0	28.0	/public/img/catalog/species_6a21731cb97c4.jpg
83649c0d-33fe-46c0-8d1a-730636dd2860	Neon Tetra	Paracheirodon innesi	Freshwater	6.0	7.0	21.0	27.0	/public/img/catalog/species_6a21734579d74.jpg
19cf4041-d26b-4e4d-948b-587731a1e55d	Ocellaris Clownfish	Amphiprion ocellaris	Saltwater	8.0	8.4	23.0	28.0	/public/img/catalog/species_6a21736259dce.jpg
\.


--
-- TOC entry 3508 (class 0 OID 16471)
-- Dependencies: 219
-- Data for Name: tank_livestock; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.tank_livestock (id_livestock, id_tank, id_species, quantity, health, added_at) FROM stdin;
37418924-f46c-4d0f-8ecb-f98c32935ac4	851f049c-f7ae-46b5-9c34-bf1da5bb088a	fbed6d84-b505-40cb-b118-e8e4e799b5d2	15	Excellent	2026-06-04 12:47:06.821146
94f2b282-244e-4383-8bb8-eec9a718a6c8	c564d224-0fc3-4e47-b627-c282d8160d3b	282e5d3d-3226-401a-b5d5-2d17c7d65782	15	Good	2026-06-04 12:49:45.682918
\.


--
-- TOC entry 3506 (class 0 OID 16443)
-- Dependencies: 217
-- Data for Name: tanks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.tanks (id_tank, id_user, name, water_type, volume_liters, width_cm, height_cm, depth_cm, status, installation_date, image_path, notes, created_at) FROM stdin;
851f049c-f7ae-46b5-9c34-bf1da5bb088a	21e760e3-2c36-48e6-afb0-4f65756ed980	Living Room tank	Freshwater	60	\N	\N	\N	Empty	\N	/public/img/tanks/tank_6a217214bbbdb.jpg	My fav aquarium	2026-06-04 12:39:48.784745
c564d224-0fc3-4e47-b627-c282d8160d3b	21e760e3-2c36-48e6-afb0-4f65756ed980	Dining Room Tank	Saltwater	150	\N	\N	\N	Empty	\N	/public/img/tanks/tank_6a21724b10219.jpg	Aquarium on photo do not resemble real one, actually it is saltwater :D\r\nphoto is only for show	2026-06-04 12:40:43.084294
fefb48ab-3f3c-4efe-9f1b-fca669902f5c	21e760e3-2c36-48e6-afb0-4f65756ed980	Tank 1	Freshwater	250	\N	\N	\N	Empty	\N	/public/img/tanks/tank_6a2171eb65451.jpg	My 1st Aquarium	2026-06-04 10:49:16.703289
b0dbe68e-2046-4992-b709-62e30a281f35	8ea1ff5c-7a9b-4c7b-a80f-7c9b61f0d4f7	Kitchen Aquarium 	Freshwater	112	\N	\N	\N	Empty	\N	/public/img/tanks/tank_6a217275ed4fd.jpg		2026-06-04 12:41:25.992338
7347af20-2113-4fe4-b5a7-e2b9126a10a6	8ea1ff5c-7a9b-4c7b-a80f-7c9b61f0d4f7	My private aquarium	Freshwater	80	\N	\N	\N	Empty	\N	/public/img/tanks/tank_6a21729599c7a.jpg		2026-06-04 12:41:57.644815
4355ed4f-bc29-4edd-9838-b8edb8612460	8ea1ff5c-7a9b-4c7b-a80f-7c9b61f0d4f7	Takashi's Amano Aquarium	Saltwater	1	\N	\N	\N	Empty	\N	/public/img/tanks/tank_6a2172b9e6897.jpg	I dont know where this is probably japan	2026-06-04 12:42:33.961109
\.


--
-- TOC entry 3505 (class 0 OID 16431)
-- Dependencies: 216
-- Data for Name: user_profiles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.user_profiles (id_user, full_name, subscription_tier, updated_at) FROM stdin;
\.


--
-- TOC entry 3504 (class 0 OID 16393)
-- Dependencies: 215
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id_user, email, password_hash, role, created_at) FROM stdin;
21e760e3-2c36-48e6-afb0-4f65756ed980	admin@company.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	admin	2026-05-31 13:35:52.656579
8ea1ff5c-7a9b-4c7b-a80f-7c9b61f0d4f7	cmkkw6@gmail.com	$2y$10$XenXR/DI1.jjUbPQuL/h1utkI.LfD1ff/tMvwzbbixTaqB4tVsbka	user	2026-05-31 13:54:46.826085
\.


--
-- TOC entry 3509 (class 0 OID 16492)
-- Dependencies: 220
-- Data for Name: water_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.water_logs (id_log, id_tank, ph_level, temperature, notes, logged_at) FROM stdin;
70b2cf5b-5531-45fc-aa59-3af282a5cb8f	fefb48ab-3f3c-4efe-9f1b-fca669902f5c	5.0	16.0	\N	2026-06-04 12:39:13.610042
035c0c0a-1913-4905-a245-af3d9c61bf3b	851f049c-f7ae-46b5-9c34-bf1da5bb088a	5.0	15.0	\N	2026-06-04 12:46:51.205777
\.


--
-- TOC entry 3351 (class 2606 OID 16513)
-- Name: installed_equipment installed_equipment_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.installed_equipment
    ADD CONSTRAINT installed_equipment_pkey PRIMARY KEY (id_equipment);


--
-- TOC entry 3341 (class 2606 OID 16468)
-- Name: species species_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.species
    ADD CONSTRAINT species_pkey PRIMARY KEY (id_species);


--
-- TOC entry 3343 (class 2606 OID 16470)
-- Name: species species_scientific_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.species
    ADD CONSTRAINT species_scientific_name_key UNIQUE (scientific_name);


--
-- TOC entry 3345 (class 2606 OID 16481)
-- Name: tank_livestock tank_livestock_id_tank_id_species_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tank_livestock
    ADD CONSTRAINT tank_livestock_id_tank_id_species_key UNIQUE (id_tank, id_species);


--
-- TOC entry 3347 (class 2606 OID 16479)
-- Name: tank_livestock tank_livestock_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tank_livestock
    ADD CONSTRAINT tank_livestock_pkey PRIMARY KEY (id_livestock);


--
-- TOC entry 3339 (class 2606 OID 16454)
-- Name: tanks tanks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tanks
    ADD CONSTRAINT tanks_pkey PRIMARY KEY (id_tank);


--
-- TOC entry 3337 (class 2606 OID 16437)
-- Name: user_profiles user_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_profiles
    ADD CONSTRAINT user_profiles_pkey PRIMARY KEY (id_user);


--
-- TOC entry 3333 (class 2606 OID 16404)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 3335 (class 2606 OID 16402)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id_user);


--
-- TOC entry 3349 (class 2606 OID 16500)
-- Name: water_logs water_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.water_logs
    ADD CONSTRAINT water_logs_pkey PRIMARY KEY (id_log);


--
-- TOC entry 3358 (class 2620 OID 16530)
-- Name: tank_livestock trg_check_livestock_compatibility; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_check_livestock_compatibility BEFORE INSERT OR UPDATE ON public.tank_livestock FOR EACH ROW EXECUTE FUNCTION public.fn_validate_water_compatibility();


--
-- TOC entry 3357 (class 2606 OID 16514)
-- Name: installed_equipment installed_equipment_id_tank_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.installed_equipment
    ADD CONSTRAINT installed_equipment_id_tank_fkey FOREIGN KEY (id_tank) REFERENCES public.tanks(id_tank) ON DELETE CASCADE;


--
-- TOC entry 3354 (class 2606 OID 16487)
-- Name: tank_livestock tank_livestock_id_species_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tank_livestock
    ADD CONSTRAINT tank_livestock_id_species_fkey FOREIGN KEY (id_species) REFERENCES public.species(id_species) ON DELETE RESTRICT;


--
-- TOC entry 3355 (class 2606 OID 16482)
-- Name: tank_livestock tank_livestock_id_tank_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tank_livestock
    ADD CONSTRAINT tank_livestock_id_tank_fkey FOREIGN KEY (id_tank) REFERENCES public.tanks(id_tank) ON DELETE CASCADE;


--
-- TOC entry 3353 (class 2606 OID 16455)
-- Name: tanks tanks_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tanks
    ADD CONSTRAINT tanks_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE CASCADE;


--
-- TOC entry 3352 (class 2606 OID 16438)
-- Name: user_profiles user_profiles_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_profiles
    ADD CONSTRAINT user_profiles_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON DELETE CASCADE;


--
-- TOC entry 3356 (class 2606 OID 16501)
-- Name: water_logs water_logs_id_tank_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.water_logs
    ADD CONSTRAINT water_logs_id_tank_fkey FOREIGN KEY (id_tank) REFERENCES public.tanks(id_tank) ON DELETE CASCADE;


-- Completed on 2026-06-04 13:30:01 UTC

--
-- PostgreSQL database dump complete
--

\unrestrict YRk5iMOjHng6F1q4HCbFcO0qfW39PaurOXrdYeOKir5ompbYQNJdPhdzlMUm1dO

