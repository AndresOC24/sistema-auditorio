<?php

namespace Database\Seeders;

use App\Models\ExternalResponsible;
use Illuminate\Database\Seeder;

/**
 * Colaboradores 2026, cargados como responsables externos de evento.
 *
 * Origen: "REGISTRO HUELLAS ADMINISTRATIVOS - COLABORADORES 2026", filas 1 a 98
 * (la sección Colaboradores; las filas 99 a 134 son Consultores y quedan
 * fuera). Las filas 2 y 23 no existen en el documento.
 *
 * Excluidos a pedido: FLORES VALDA MIGUEL ANGEL (31) y ORTEGA CRUZ FABIO
 * ANDRES (53).
 *
 * Los nombres se guardan verbatim aquí, en mayúsculas como en el documento,
 * para poder auditarlos contra la fuente; al sembrar se pasan a formato de
 * título, que es más legible en el selector del formulario de eventos.
 *
 * Idempotente: volver a correrlo no duplica.
 */
class ExternalResponsibleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const COLABORADORES_2026 = [
        'ABALOS TOLEDO JOSE KLEVERT',
        'AGUAYO WILLS MELISSA CAROLINA',
        'AGUILAR MOZA LUIS GABRIEL',
        'ALBA HERRERA MIJAIL ERWIN',
        'ALIAGA BACARREZA CLAUDIA MIREYA',
        'ANTEZANA VASQUEZ CARMEN DANIELA',
        'ARZE MUÑOZ ADRIANA MARIA',
        'AVILES ROJAS LESLIE PATRICIA',
        'BALDELOMAR DAZA KATERINE ELIANA',
        'BARRIENTOS MORALES DANIELA ISABEL',
        'BELLO SEGOVIA GRECIA SHAIEL',
        'BLANCO ZABALA ELIZABETH',
        'CABALEIRO PATRICIA NORA',
        'CAMPERO ACUÑA GISMAR',
        'CANIDO REYES LUZ ESTRELLA',
        'CARRILLO FIGUEROA EDISON',
        'CASTILLO SANCHEZ SANDRA',
        'CASTRO PALACIOS KARIN GABRIELA',
        'CHAVEZ SIVILA NINOSKA ADRIANA',
        'COSSIO OLIVARES LAURA ANDREA',
        'CUELLAR CRESPO CARLOS JAVIER',
        'DE LA ROSA POZO CLAUDIO ALEJANDRO',
        'DEL CASTILLO FRANCO PATRICIA',
        'DELGADO CHUMACERO ALEJANDRA',
        'DOMINGUEZ ARAUJO LITA ANA',
        'ENDARA DAGUER DANIELA',
        'ESPINOZA MELGAR JESSICA DANIELLE',
        'FLORES CAMARGO HELENA PATRICIA',
        'GARCIA TARDIO GABRIEL REYNALDO',
        'GONZALES POVEDA JUAN PABLO',
        'GUTIERREZ SHUGAR ALBERT JOEL',
        'JIMENEZ CHOQUE PAMELA MABEL',
        'KISHIMOTO VARGAS JUAN MARCELO',
        'LAFUENTE APONTE KAREN FABIOLA',
        'LEDEZMA SANCHEZ YULEMI',
        'LIMPIAS FRANCO LAURA',
        'LOPEZ CRESPO MIGUEL ANGEL MARTIN',
        'LOPEZ ZAPANA TATIANA',
        'MACHACA CHOQUE LEANDRA LIZETH',
        'MANCILLA CRUZ CAROLINA',
        'MEDINA NINA KAREN ADRIANA',
        'MENDEZ AÑEZ YESENIA',
        'MIRANDA VILLARROEL CAROLA REGINA',
        'MONTAÑO REY YULIANE',
        'MONTERO PEREZ SILVIO',
        'MOYA ZERNA ANGÉLICA ROSARIO',
        'MUÑOZ MUÑOZ MARIA ISABEL',
        'OLLE GONZALEZ MARIA FERNANDA',
        'OPIMI GONZALES JORGE LUIS',
        'ORTIZ AGUILERA CARLA PATRICIA',
        'ORTUÑO TOLEDO JUAN CARLOS',
        'PACHECO USTAREZ HERMAN MARCELO',
        'PAEZ ANDRADE LUIS ENRIQUE',
        'PARADA GUACHALLA LUIS ANTONIO',
        'PAZOS GARRIDO JORGE',
        'PERALTA MIRANDA THAIS NAHOMI',
        'PEREDO CAMACHO KEVIN FREDDY',
        'PEREZ COCA DIDRIANA',
        'PINTO CARVAJAL CLAUDIA SORAYA',
        'PIZARRO LOAYZA MARIA FERNANDA',
        'PIZARRO TORREZ EVELIN',
        'POVEDA PALACIOS LAURA',
        'RAMIREZ VOLTAIRE MARIA YOLANDA ROSARIO',
        'REJALA IRAHOLA SHIRLEY JUDITH',
        'REYES CORTIÑAS JORGE LUIS',
        'RIVERA SEGOVIA JHONNY JAVIER',
        'RIVEROS MAMANI GERMAN',
        'ROBLES PINTO JAMES YHON',
        'ROCHA CISNEROS IVONNE DE LA CARIDAD',
        'ROJAS CARREÑO FABIOLA',
        'SAAVEDRA NAGAYAMA HAMMELETH FROILAN',
        'SANCHEZ SOLIZ ANDREA CONSUELO',
        'SANDOVAL H GUTIERREZ MAITE',
        'SILES WINTER ERWIN',
        'SOLANO GARVIZU WHARA',
        'SUAREZ MEDINA ANTONIO',
        'SUAREZ SUAREZ BARBARA FERNANDA',
        'TANGO CAMARGO ESTELA MARIA DEL CARMEN',
        'TAPIA MAMANI LIZBETH MICHELLE',
        'ULLOA BERSATTI LORENA',
        'ULLOA GUSHIKEN KAREN XIMENA',
        'URIONA HERRERA CRISTHIAN FABIAN',
        'URIOSTE VASQUEZ CECILIA',
        'VACA TORRICO ERIKA',
        'VARANI MOJICA KENIA CAROLA',
        'VARGAS MEJIA LAURA FABIOLA',
        'VARGAS MELGAR ANA LIZ VERONICA',
        'VIDAL HUERTA GONZALO IVAN',
        'VIERA HERNANDEZ RAIZA VERÓNICA',
        'YOSHINO OTSUKA SARA VIRGINIA',
        'ZABALA PEREZ JOSE BERNARDO',
        'ZAMBRANA HOYOS XIOMARA',
        'ZANKIS SAUCEDO LUIS ANDRES',
        'ZEGARRA SALDAÑA ALEJANDRO',
    ];

    public function run(): void
    {
        foreach (self::COLABORADORES_2026 as $name) {
            ExternalResponsible::firstOrCreate(['name' => self::asTitle($name)]);
        }

        $this->command->info(sprintf(
            '%d colaboradores cargados como responsables externos (%d en total).',
            count(self::COLABORADORES_2026),
            ExternalResponsible::count(),
        ));
    }

    /**
     * "ARZE MUÑOZ ADRIANA MARIA" → "Arze Muñoz Adriana Maria".
     *
     * Se conserva el orden apellidos-nombres del documento para que el listado
     * ordene igual que la fuente.
     */
    private static function asTitle(string $name): string
    {
        return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
