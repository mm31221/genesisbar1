<?php

function productos_menu()
{
    return [
        "combo_clasicas_12" => [
            "nombre" => "12 Piezas Clasicas",
            "precio" => 12800,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "Philadelphia + Ebi + California"
        ],
        "combo_clasicas_24" => [
            "nombre" => "24 Piezas Clasicas",
            "precio" => 25600,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "12 piezas clasicas + Stowe + New York + Maki"
        ],
        "combo_clasicas_40" => [
            "nombre" => "40 Piezas Clasicas",
            "precio" => 42800,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "36 piezas clasicas + Tuna"
        ],
        "combo_premium_12" => [
            "nombre" => "12 Piezas Premium",
            "precio" => 14800,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "Mix + Bittersweet + Hot Phila"
        ],
        "combo_premium_24" => [
            "nombre" => "24 Piezas Premium",
            "precio" => 29600,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "12 piezas premium + Tempura + New York + Maki"
        ],
        "combo_premium_40" => [
            "nombre" => "40 Piezas Premium",
            "precio" => 49600,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "36 piezas premium + Philadelphia"
        ],
        "combo_hot_rolls_12" => [
            "nombre" => "12 Piezas Hot Rolls",
            "precio" => 14800,
            "categoria" => "Sushi - Combinados",
            "sector" => "horno_freidora",
            "descripcion" => "Hot Cali + Tempura + Hot Phila"
        ],
        "combo_veggie_12" => [
            "nombre" => "12 Piezas Veggie",
            "precio" => 12800,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "Avocado + Capresse + Carrot"
        ],
        "combo_veggie_24" => [
            "nombre" => "24 Piezas Veggie",
            "precio" => 25600,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "16 piezas veggie + Hot Avocado + Maki"
        ],
        "combo_veggie_40" => [
            "nombre" => "40 Piezas Veggie",
            "precio" => 42800,
            "categoria" => "Sushi - Combinados",
            "sector" => "piezas_frias",
            "descripcion" => "40 piezas vegetarianas"
        ],
        "clasico_philadelphia_8" => ["nombre" => "Philadelphia - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Salmon, palta y queso"],
        "clasico_stowe_8" => ["nombre" => "Stowe - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Salmon cocido, palta y queso"],
        "clasico_new_york_8" => ["nombre" => "New York - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Salmon y palta"],
        "clasico_ebi_8" => ["nombre" => "Ebi - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Langostinos, palta y queso"],
        "clasico_california_8" => ["nombre" => "California - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Kanikama, palta y queso"],
        "clasico_tuna_8" => ["nombre" => "Tuna - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Atun y queso"],
        "geishas_6" => ["nombre" => "Geishas - 6 piezas", "precio" => 10900, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Salmon ahumado, palta y queso"],
        "nigiri_6" => ["nombre" => "Nigiri - 6 piezas", "precio" => 10900, "categoria" => "Sushi - Clasicos", "sector" => "piezas_frias", "descripcion" => "Salmon, langostinos o kanikama"],
        "premium_honey_8" => ["nombre" => "Honey - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Premium", "sector" => "piezas_frias", "descripcion" => "Salmon cocido en base dulce con salsa honey"],
        "premium_teriyaki_8" => ["nombre" => "Teriyaki - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Premium", "sector" => "piezas_frias", "descripcion" => "Salmon, palta y queso con salsa teriyaki"],
        "premium_mix_8" => ["nombre" => "Mix - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Premium", "sector" => "piezas_frias", "descripcion" => "Salmon ahumado y queso con emulsion de langostinos y kanikama"],
        "premium_bittersweet_8" => ["nombre" => "Bittersweet - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Premium", "sector" => "horno_freidora", "descripcion" => "Langostinos fritos y queso con salsa honey"],
        "premium_mm_8" => ["nombre" => "MM - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Premium", "sector" => "horno_freidora", "descripcion" => "Pollo frito con queso y verdeo"],
        "premium_maki_8" => ["nombre" => "Maki - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Premium", "sector" => "piezas_frias", "descripcion" => "Salmon o kanikama"],
        "hot_cali_8" => ["nombre" => "Hot Cali - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Hot Roll", "sector" => "horno_freidora", "descripcion" => "Kanikama, palta y queso rebozado en panko y frito"],
        "hot_new_york_8" => ["nombre" => "Hot New York - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Hot Roll", "sector" => "horno_freidora", "descripcion" => "Salmon y palta rebozado en panko y frito"],
        "hot_phila_8" => ["nombre" => "Hot Phila - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Hot Roll", "sector" => "horno_freidora", "descripcion" => "Salmon ahumado, palta y queso rebozado en panko y frito"],
        "hot_heavy_8" => ["nombre" => "Heavy - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Hot Roll", "sector" => "horno_freidora", "descripcion" => "Langostinos, cheddar y verdeo rebozado en panko y frito"],
        "hot_tempura_8" => ["nombre" => "Tempura - 8 piezas", "precio" => 10900, "categoria" => "Sushi - Hot Roll", "sector" => "horno_freidora", "descripcion" => "Langostinos, queso y verdeo envuelto en masa tempura y frito"],
        "veggie_avocado_8" => ["nombre" => "Avocado - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Veggie", "sector" => "piezas_frias", "descripcion" => "Palta, queso y verdeo"],
        "veggie_hot_avocado_8" => ["nombre" => "Hot Avocado - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Veggie", "sector" => "horno_freidora", "descripcion" => "Palta, queso y verdeo apanado en panko y frito"],
        "veggie_capresse_8" => ["nombre" => "Capresse - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Veggie", "sector" => "piezas_frias", "descripcion" => "Tomates disecados, albahaca y queso"],
        "veggie_carrot_8" => ["nombre" => "Carrot - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Veggie", "sector" => "piezas_frias", "descripcion" => "Zanahoria caramelizada y queso con caviar de berenjenas"],
        "veggie_maki_8" => ["nombre" => "Maki Veggie - 8 piezas", "precio" => 9500, "categoria" => "Sushi - Veggie", "sector" => "piezas_frias", "descripcion" => "Tomates disecados o pepino"],
        "pizza_muzzarella" => ["nombre" => "Pizza Muzzarella", "precio" => 8100, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => "Chimichurri pizzero opcional"],
        "pizza_fugaza" => ["nombre" => "Pizza Fugaza", "precio" => 8500, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => ""],
        "pizza_capresse" => ["nombre" => "Pizza Capresse", "precio" => 9400, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => "Tomate y albahaca"],
        "pizza_napolitana" => ["nombre" => "Pizza Napolitana", "precio" => 9400, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => "Tomate y ajo"],
        "pizza_especial" => ["nombre" => "Pizza Especial", "precio" => 10500, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => "Jamon y morron asado"],
        "pizza_primavera" => ["nombre" => "Pizza Primavera", "precio" => 10500, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => "Huevo, tomate y jamon"],
        "pizza_calabresa" => ["nombre" => "Pizza Calabresa", "precio" => 10500, "categoria" => "Pizzas", "sector" => "horno_freidora", "descripcion" => ""],
        "promo_2_muzzarella" => ["nombre" => "Promo 2 pizzas muzzarella", "precio" => 14500, "categoria" => "Promos", "sector" => "horno_freidora", "descripcion" => ""],
        "promo_especial_muzza" => ["nombre" => "Promo especial + muzza", "precio" => 16500, "categoria" => "Promos", "sector" => "horno_freidora", "descripcion" => "1 pizza especial + 1 muzzarella"],
        "promo_muzza_gaseosa" => ["nombre" => "Promo muzza + gaseosa", "precio" => 10500, "categoria" => "Promos", "sector" => "horno_freidora", "descripcion" => ""],
        "promo_docena_empanadas_clasicas" => ["nombre" => "Promo docena empanadas clasicas", "precio" => 13000, "categoria" => "Promos", "sector" => "horno_freidora", "descripcion" => "Adicional de especiales $100"],
        "promo_muzza_6_empanadas" => ["nombre" => "Promo muzza + 6 empanadas", "precio" => 14000, "categoria" => "Promos", "sector" => "horno_freidora", "descripcion" => "Empanadas clasicas"],
        "empanada_capresse" => ["nombre" => "Empanada Capresse", "precio" => 1200, "categoria" => "Empanadas Clasicas", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_cebolla_queso" => ["nombre" => "Empanada Cebolla y Queso", "precio" => 1200, "categoria" => "Empanadas Clasicas", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_carne" => ["nombre" => "Empanada Carne", "precio" => 1200, "categoria" => "Empanadas Clasicas", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_pollo" => ["nombre" => "Empanada Pollo", "precio" => 1200, "categoria" => "Empanadas Clasicas", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_jamon_queso" => ["nombre" => "Empanada Jamon y Queso", "precio" => 1200, "categoria" => "Empanadas Clasicas", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_calabresa" => ["nombre" => "Empanada Calabresa", "precio" => 1300, "categoria" => "Empanadas Especiales", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_chesse_burger" => ["nombre" => "Empanada Chesse burger", "precio" => 1300, "categoria" => "Empanadas Especiales", "sector" => "horno_freidora", "descripcion" => ""],
        "empanada_pollo_verdeo" => ["nombre" => "Empanada Pollo al verdeo", "precio" => 1300, "categoria" => "Empanadas Especiales", "sector" => "horno_freidora", "descripcion" => ""],
        "salsa_teriyaki" => ["nombre" => "Salsa Teriyaki", "precio" => 1400, "categoria" => "Salsas", "sector" => "piezas_frias", "descripcion" => ""],
        "salsa_buenos_aires" => ["nombre" => "Salsa Buenos Aires", "precio" => 1400, "categoria" => "Salsas", "sector" => "piezas_frias", "descripcion" => ""],
        "salsa_honey" => ["nombre" => "Salsa Honey", "precio" => 1400, "categoria" => "Salsas", "sector" => "piezas_frias", "descripcion" => ""],
        "baston_pollo" => ["nombre" => "Bastones de pollo - 4 unidades", "precio" => 3900, "categoria" => "Bastones", "sector" => "horno_freidora", "descripcion" => ""],
        "baston_muzzarella" => ["nombre" => "Bastones de muzzarella - 4 unidades", "precio" => 3900, "categoria" => "Bastones", "sector" => "horno_freidora", "descripcion" => ""],
        "papas_porcion" => ["nombre" => "Papas fritas porcion", "precio" => 3800, "categoria" => "Papas Fritas", "sector" => "horno_freidora", "descripcion" => ""],
        "papas_cono" => ["nombre" => "Cono de papas", "precio" => 3500, "categoria" => "Papas Fritas", "sector" => "horno_freidora", "descripcion" => "Para llevar"],
        "papas_cheddar_verdeo" => ["nombre" => "Papas con cheddar y verdeo", "precio" => 5100, "categoria" => "Papas Fritas", "sector" => "horno_freidora", "descripcion" => ""],
        "gaseosa_coca" => ["nombre" => "Coca Cola 1 1/2 lts", "precio" => 3500, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "gaseosa_fanta" => ["nombre" => "Fanta 1 1/2 lts", "precio" => 3500, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "gaseosa_sprite" => ["nombre" => "Sprite 1 1/2 lts", "precio" => 3500, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "saborizada_naranja" => ["nombre" => "Saborizada naranja 1 1/2 lts", "precio" => 2400, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "saborizada_pomelo" => ["nombre" => "Saborizada pomelo 1 1/2 lts", "precio" => 2400, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "saborizada_manzana" => ["nombre" => "Saborizada manzana 1 1/2 lts", "precio" => 2400, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "agua_chica" => ["nombre" => "Agua chica", "precio" => 2400, "categoria" => "Bebidas", "sector" => "barra", "descripcion" => ""],
        "jarra_campari" => ["nombre" => "Jarra Campari", "precio" => 7600, "categoria" => "Jarras con alcohol", "sector" => "barra", "descripcion" => ""],
        "jarra_gancia" => ["nombre" => "Jarra Gancia", "precio" => 7600, "categoria" => "Jarras con alcohol", "sector" => "barra", "descripcion" => ""],
        "jarra_fernet" => ["nombre" => "Jarra Fernet", "precio" => 7600, "categoria" => "Jarras con alcohol", "sector" => "barra", "descripcion" => ""],
        "jarra_ron" => ["nombre" => "Jarra Ron", "precio" => 7600, "categoria" => "Jarras con alcohol", "sector" => "barra", "descripcion" => ""],
        "jarra_gin" => ["nombre" => "Jarra Gin", "precio" => 7600, "categoria" => "Jarras con alcohol", "sector" => "barra", "descripcion" => "Limon, pepino o frutos rojos"],
        "jarra_vodka" => ["nombre" => "Jarra Vodka", "precio" => 7600, "categoria" => "Jarras con alcohol", "sector" => "barra", "descripcion" => "Jugo o frutos rojos"],
        "jarra_limonada" => ["nombre" => "Jarra limonada", "precio" => 6500, "categoria" => "Jarras sin alcohol", "sector" => "barra", "descripcion" => "Limon, lima, menta y jengibre"],
        "jarra_frutos_rojos" => ["nombre" => "Jarra frutos rojos", "precio" => 6500, "categoria" => "Jarras sin alcohol", "sector" => "barra", "descripcion" => "Frutillas, arandanos, menta, tonica o sprite"],
        "cerveza_amstel" => ["nombre" => "Amstel 1 ltr", "precio" => 5100, "categoria" => "Cervezas", "sector" => "barra", "descripcion" => ""],
        "cerveza_brahama" => ["nombre" => "Brahama 1 ltr", "precio" => 5100, "categoria" => "Cervezas", "sector" => "barra", "descripcion" => ""],
        "cerveza_heineken_lata" => ["nombre" => "Heineken lata", "precio" => 3800, "categoria" => "Cervezas", "sector" => "barra", "descripcion" => ""],
        "vino_elementos_tinto" => ["nombre" => "Elementos tinto", "precio" => 6100, "categoria" => "Vinos", "sector" => "barra", "descripcion" => ""],
        "vino_elementos_blanco" => ["nombre" => "Elementos blanco", "precio" => 6100, "categoria" => "Vinos", "sector" => "barra", "descripcion" => ""],
        "trago" => ["nombre" => "Trago", "precio" => 3100, "categoria" => "Tragos", "sector" => "barra", "descripcion" => "Campari, Gancia, Ron, Fernet, Gin o Vodka"],
        "sushi" => ["nombre" => "Sushi", "precio" => 18000, "categoria" => "Varios", "sector" => "piezas_frias", "descripcion" => "Producto anterior"],
        "pizza" => ["nombre" => "Pizza", "precio" => 12000, "categoria" => "Varios", "sector" => "horno_freidora", "descripcion" => "Producto anterior"],
        "empanadas" => ["nombre" => "Empanadas", "precio" => 1500, "categoria" => "Varios", "sector" => "horno_freidora", "descripcion" => "Producto anterior"]
    ];
}

function producto_menu($codigo)
{
    $productos = productos_menu();

    return $productos[$codigo] ?? null;
}

function nombre_producto($codigo)
{
    $producto = producto_menu($codigo);

    if ($producto) {
        return $producto["nombre"];
    }

    return ucfirst(str_replace("_", " ", $codigo));
}

function precio_producto($codigo)
{
    $producto = producto_menu($codigo);

    return $producto ? $producto["precio"] : null;
}

function sector_producto($codigo)
{
    $producto = producto_menu($codigo);

    return $producto ? $producto["sector"] : "otros";
}

function descripcion_producto($codigo)
{
    $producto = producto_menu($codigo);

    return $producto ? $producto["descripcion"] : "";
}

function categorias_menu()
{
    $categorias = [];

    foreach (productos_menu() as $codigo => $producto) {
        if ($producto["categoria"] === "Varios") {
            continue;
        }

        $categorias[$producto["categoria"]][$codigo] = $producto;
    }

    return $categorias;
}

function texto_sector_cocina($sector)
{
    $sectores = [
        "piezas_frias" => "Piezas frias / Sushi",
        "horno_freidora" => "Horno y freidora",
        "barra" => "Barra / Bebidas",
        "otros" => "Sin sector"
    ];

    return $sectores[$sector] ?? "Sin sector";
}

?>
