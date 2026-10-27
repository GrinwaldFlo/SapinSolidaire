<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Registry of the texts organizers can personalise (family pages and emails).
 *
 * Each text is stored as a Setting. When the stored value is empty, the default
 * text is used. Placeholders such as {site} are replaced when the text is read.
 */
class CustomText
{
    public const HOME_INTRODUCTION = Setting::INTRODUCTION_TEXT;

    public const REGISTRATION_BLOCKED = Setting::REGISTRATION_BLOCKED_REASON;

    public const REGISTRATION_DEADLINE_PASSED = 'text_registration_deadline_passed';

    public const REGISTRATION_GIFT_LIMIT_REACHED = 'text_registration_gift_limit_reached';

    public const SEASON_FUTURE = 'text_season_future';

    public const SEASON_NONE = 'text_season_none';

    public const HOME_PICKUP_SLOTS_INFO = 'text_home_pickup_slots_info';

    public const FORM_SUBMITTED = 'text_form_submitted';

    public const FORM_PERMANENTLY_REJECTED = 'text_form_permanently_rejected';

    public const FORM_CONSECUTIVE_YEARS_REACHED = 'text_form_consecutive_years_reached';

    public const MAIL_SIGNATURE = 'text_mail_signature';

    public const MAIL_FOOTER_NO_REPLY = 'text_mail_footer_no_reply';

    public const MAIL_FOOTER_REPLY = 'text_mail_footer_reply';

    public const MAIL_ACCESS_LINK_SUBJECT = 'text_mail_access_link_subject';

    public const MAIL_ACCESS_LINK_INTRO = 'text_mail_access_link_intro';

    public const MAIL_ACCESS_LINK_CLOSING = 'text_mail_access_link_closing';

    public const MAIL_CORRECTION_SUBJECT = 'text_mail_correction_subject';

    public const MAIL_CORRECTION_INTRO = 'text_mail_correction_intro';

    public const MAIL_CORRECTION_CLOSING = 'text_mail_correction_closing';

    public const MAIL_REJECTION_SUBJECT = 'text_mail_rejection_subject';

    public const MAIL_REJECTION_INTRO = 'text_mail_rejection_intro';

    public const MAIL_REJECTION_CLOSING = 'text_mail_rejection_closing';

    public const MAIL_REQUEST_COMPLETE_SUBJECT = 'text_mail_request_complete_subject';

    public const MAIL_REQUEST_COMPLETE_INTRO = 'text_mail_request_complete_intro';

    public const MAIL_REQUEST_COMPLETE_BODY = 'text_mail_request_complete_body';

    public const MAIL_GIFT_RECEIVED_SUBJECT = 'text_mail_gift_received_subject';

    public const MAIL_GIFT_RECEIVED_INTRO = 'text_mail_gift_received_intro';

    public const MAIL_GIFT_RECEIVED_REMINDERS = 'text_mail_gift_received_reminders';

    public const MAIL_GIFT_RECEIVED_CLOSING = 'text_mail_gift_received_closing';

    private const FAMILY_MAIL_INTRO = "Vous avez inscrit vos enfants au Sapin solidaire afin qu'ils reçoivent un cadeau de Noël.";

    private const PLACEHOLDER_SITE = ['{site}' => 'Nom du site'];

    private const PLACEHOLDER_FAMILY = ['{famille}' => 'Nom de famille'];

    /**
     * Memoized result of definitions() (texts are static, built once per process).
     *
     * @var array<string, array{label: string, default: string, multiline: bool, help?: string, placeholders?: array<string, string>}>|null
     */
    private static ?array $definitions = null;

    /**
     * Texts grouped by section, in display order.
     *
     * @return array<string, array{title: string, texts: array<string, array{label: string, default: string, multiline: bool, help?: string, placeholders?: array<string, string>}>}>
     */
    public static function groups(): array
    {
        return [
            'home' => [
                'title' => "Page d'accueil",
                'texts' => [
                    self::HOME_INTRODUCTION => [
                        'label' => "Texte d'introduction",
                        'default' => '',
                        'multiline' => true,
                        'help' => "Affiché aux familles sur la page d'accueil. Laissez vide pour ne rien afficher.",
                    ],
                    self::HOME_PICKUP_SLOTS_INFO => [
                        'label' => 'Explication des créneaux de récupération',
                        'default' => "Vous devrez être disponible lors de l'un de ces créneaux. Vous recevrez une invitation précisant le créneau auquel vous devrez vous présenter.",
                        'multiline' => true,
                        'help' => "Affiché dans l'agenda, au-dessus de la liste des créneaux.",
                    ],
                    self::SEASON_FUTURE => [
                        'label' => 'Saison pas encore ouverte',
                        'default' => 'La prochaine période de demandes ouvrira le {date_ouverture}. Revenez à cette date pour faire votre demande.',
                        'multiline' => true,
                        'placeholders' => ['{date_ouverture}' => "Date d'ouverture de la saison"],
                    ],
                    self::SEASON_NONE => [
                        'label' => 'Aucune saison programmée',
                        'default' => "Aucune période de demandes n'est actuellement programmée. Veuillez revenir plus tard.",
                        'multiline' => true,
                    ],
                ],
            ],
            'registration' => [
                'title' => 'Inscriptions closes',
                'texts' => [
                    self::REGISTRATION_BLOCKED => [
                        'label' => 'Motif du blocage des inscriptions',
                        'default' => 'Les inscriptions sont momentanément fermées.',
                        'multiline' => true,
                        'help' => "Affiché sur la page d'accueil et le formulaire lorsque les inscriptions sont bloquées dans les paramètres.",
                    ],
                    self::REGISTRATION_DEADLINE_PASSED => [
                        'label' => 'Date limite dépassée',
                        'default' => 'Les inscriptions sont closes. La date limite était fixée au {date_limite}.',
                        'multiline' => true,
                        'placeholders' => ['{date_limite}' => 'Date limite de modification de la saison'],
                    ],
                    self::REGISTRATION_GIFT_LIMIT_REACHED => [
                        'label' => 'Nombre maximum de cadeaux atteint',
                        'default' => 'Les inscriptions sont closes : le nombre maximum de cadeaux pour cette saison a été atteint.',
                        'multiline' => true,
                        'help' => "Affiché sur la page d'accueil et le formulaire lorsque le nombre maximum de cadeaux de la saison est atteint. Les familles déjà inscrites peuvent modifier leur demande, mais pas ajouter d'enfant.",
                    ],
                ],
            ],
            'form' => [
                'title' => 'Formulaire de demande',
                'texts' => [
                    self::FORM_SUBMITTED => [
                        'label' => 'Demande enregistrée',
                        'default' => 'Nous examinerons votre demande et vous contacterons par e-mail.',
                        'multiline' => true,
                        'help' => "Affiché après l'envoi ou la modification de la demande.",
                    ],
                    self::FORM_PERMANENTLY_REJECTED => [
                        'label' => 'Famille refusée définitivement',
                        'default' => 'Votre famille a été refusée, vous ne pouvez pas effectuer de demande de cadeau cette année.',
                        'multiline' => true,
                    ],
                    self::FORM_CONSECUTIVE_YEARS_REACHED => [
                        'label' => "Limite d'années consécutives atteinte",
                        'default' => "Votre famille a déjà fait une demande de cadeau en {annees}.\nPour que d'autres familles puissent aussi recevoir un cadeau, on ne peut pas faire une demande plusieurs années de suite.\nVous pourrez faire une nouvelle demande l'année prochaine. Nous vous souhaitons de belles fêtes !",
                        'multiline' => true,
                        'placeholders' => ['{annees}' => 'Années des demandes précédentes (ex: 2024 et en 2025)'],
                    ],
                ],
            ],
            'mail_common' => [
                'title' => 'E-mails : éléments communs',
                'texts' => [
                    self::MAIL_SIGNATURE => [
                        'label' => 'Signature',
                        'default' => 'Au nom du comité de Sapin Solidaire',
                        'multiline' => true,
                        'help' => 'Utilisée dans les e-mails « Demande complète » et « Cadeaux prêts », suivie du contact responsable de la saison.',
                        'placeholders' => self::PLACEHOLDER_SITE,
                    ],
                    self::MAIL_FOOTER_NO_REPLY => [
                        'label' => 'Pied de page (lien, correction, refus)',
                        'default' => 'Cet e-mail a été envoyé automatiquement. Merci de ne pas y répondre directement.',
                        'multiline' => true,
                    ],
                    self::MAIL_FOOTER_REPLY => [
                        'label' => 'Pied de page (demande complète, cadeaux prêts)',
                        'default' => 'Cet e-mail a été envoyé automatiquement. Vous pouvez y répondre directement si vous avez des questions.',
                        'multiline' => true,
                    ],
                ],
            ],
            'mail_access_link' => [
                'title' => "E-mail : lien d'accès au formulaire",
                'texts' => [
                    self::MAIL_ACCESS_LINK_SUBJECT => [
                        'label' => 'Sujet',
                        'default' => 'Votre demande de cadeau - {site}',
                        'multiline' => false,
                        'placeholders' => self::PLACEHOLDER_SITE,
                    ],
                    self::MAIL_ACCESS_LINK_INTRO => [
                        'label' => 'Introduction (avant le lien)',
                        'default' => 'Vous avez demandé à accéder au formulaire de demande de cadeau.',
                        'multiline' => true,
                    ],
                    self::MAIL_ACCESS_LINK_CLOSING => [
                        'label' => 'Conclusion (après le lien)',
                        'default' => "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.",
                        'multiline' => true,
                    ],
                ],
            ],
            'mail_correction' => [
                'title' => 'E-mail : demande de correction',
                'texts' => [
                    self::MAIL_CORRECTION_SUBJECT => [
                        'label' => 'Sujet',
                        'default' => 'Votre demande nécessite une correction - {site}',
                        'multiline' => false,
                        'placeholders' => self::PLACEHOLDER_SITE,
                    ],
                    self::MAIL_CORRECTION_INTRO => [
                        'label' => 'Introduction (avant le motif)',
                        'default' => 'Nous avons examiné votre demande de cadeau et nous avons besoin que vous apportiez une correction.',
                        'multiline' => true,
                    ],
                    self::MAIL_CORRECTION_CLOSING => [
                        'label' => 'Conclusion (après le lien)',
                        'default' => "Si vous avez des questions, n'hésitez pas à nous contacter.",
                        'multiline' => true,
                    ],
                ],
            ],
            'mail_rejection' => [
                'title' => 'E-mail : refus définitif',
                'texts' => [
                    self::MAIL_REJECTION_SUBJECT => [
                        'label' => 'Sujet',
                        'default' => 'Information concernant votre demande - {site}',
                        'multiline' => false,
                        'placeholders' => self::PLACEHOLDER_SITE,
                    ],
                    self::MAIL_REJECTION_INTRO => [
                        'label' => 'Introduction (avant le motif)',
                        'default' => 'Nous avons examiné votre demande de cadeau et nous sommes dans le regret de vous informer que nous ne pouvons pas y donner suite.',
                        'multiline' => true,
                    ],
                    self::MAIL_REJECTION_CLOSING => [
                        'label' => 'Conclusion (après le motif)',
                        'default' => "Nous vous remercions de votre compréhension.\n\nSi vous pensez qu'il s'agit d'une erreur, vous pouvez nous contacter.",
                        'multiline' => true,
                    ],
                ],
            ],
            'mail_request_complete' => [
                'title' => 'E-mail : demande complète',
                'texts' => [
                    self::MAIL_REQUEST_COMPLETE_SUBJECT => [
                        'label' => 'Sujet',
                        'default' => 'Votre demande est complète - {site}',
                        'multiline' => false,
                        'placeholders' => self::PLACEHOLDER_SITE,
                    ],
                    self::MAIL_REQUEST_COMPLETE_INTRO => [
                        'label' => 'Introduction (avant la liste des enfants)',
                        'default' => self::FAMILY_MAIL_INTRO,
                        'multiline' => true,
                        'placeholders' => self::PLACEHOLDER_FAMILY,
                    ],
                    self::MAIL_REQUEST_COMPLETE_BODY => [
                        'label' => 'Message principal',
                        'default' => "Votre demande est à présent complète et ne peut plus être modifiée. Vous recevrez prochainement un message pour venir chercher les cadeaux souhaités {date_retrait} à l'heure qui vous sera indiquée.",
                        'multiline' => true,
                        'placeholders' => ['{date_retrait}' => 'Date(s) de retrait de la saison (ex: le samedi 19 décembre 2026), vide si aucun créneau'] + self::PLACEHOLDER_FAMILY,
                    ],
                ],
            ],
            'mail_gift_received' => [
                'title' => 'E-mail : cadeaux prêts (confirmation)',
                'texts' => [
                    self::MAIL_GIFT_RECEIVED_SUBJECT => [
                        'label' => 'Sujet',
                        'default' => 'Les cadeaux sont prêts ! - {site}',
                        'multiline' => false,
                        'placeholders' => self::PLACEHOLDER_SITE,
                    ],
                    self::MAIL_GIFT_RECEIVED_INTRO => [
                        'label' => 'Introduction',
                        'default' => self::FAMILY_MAIL_INTRO,
                        'multiline' => true,
                        'placeholders' => self::PLACEHOLDER_FAMILY,
                    ],
                    self::MAIL_GIFT_RECEIVED_REMINDERS => [
                        'label' => 'Rappels (après le créneau)',
                        'default' => "📋 N'oubliez pas de prendre avec vous votre pièce d'identité et celles de vos enfants.\n🛍️ Pensez également à prendre un grand sac avec vous pour y glisser les cadeaux qui sont parfois volumineux.",
                        'multiline' => true,
                    ],
                    self::MAIL_GIFT_RECEIVED_CLOSING => [
                        'label' => 'Conclusion',
                        'default' => 'Nous nous réjouissons de vous voir !',
                        'multiline' => true,
                        'placeholders' => self::PLACEHOLDER_FAMILY,
                    ],
                ],
            ],
        ];
    }

    /**
     * All text definitions keyed by setting key.
     *
     * @return array<string, array{label: string, default: string, multiline: bool, help?: string, placeholders?: array<string, string>}>
     */
    public static function definitions(): array
    {
        return self::$definitions ??= array_merge(...array_values(array_map(
            static fn (array $group): array => $group['texts'],
            self::groups()
        )));
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Placeholders available for a text ({site} is always available).
     *
     * @return array<string, string>
     */
    public static function placeholders(string $key): array
    {
        return self::PLACEHOLDER_SITE + (self::definitions()[$key]['placeholders'] ?? []);
    }

    /**
     * Placeholders used in a text that are not available for it, e.g. ['{famille}'].
     *
     * @return array<int, string>
     */
    public static function unknownPlaceholders(string $key, string $text): array
    {
        preg_match_all('/\{[^{}\s]+\}/u', $text, $matches);

        return array_values(array_diff(array_unique($matches[0]), array_keys(self::placeholders($key))));
    }

    public static function default(string $key): string
    {
        return self::definitions()[$key]['default'] ?? '';
    }

    /**
     * Stored (custom) value, empty string when the default text is used.
     */
    public static function stored(string $key): string
    {
        return trim((string) Setting::getValue($key, ''));
    }

    /**
     * Get the text to display: custom value or default, with placeholders replaced.
     *
     * @param  array<string, string>  $replacements  Placeholder name (without braces) => value
     */
    public static function get(string $key, array $replacements = []): string
    {
        $text = self::stored($key);
        if ($text === '') {
            $text = self::default($key);
        }

        if ($text === '') {
            return '';
        }

        $map = ['{site}' => Setting::getSiteName()];
        foreach ($replacements as $name => $value) {
            $map['{'.$name.'}'] = (string) $value;
        }

        return trim(strtr(self::removeEmptyPlaceholders($text, $map), $map));
    }

    /**
     * Remove placeholders replaced by an empty value, collapsing the spaces around
     * them into one so the surrounding text keeps its own spacing and line breaks.
     *
     * @param  array<string, string>  $map  Placeholder (with braces) => value
     */
    private static function removeEmptyPlaceholders(string $text, array $map): string
    {
        $empty = array_keys(array_filter($map, static fn (string $value): bool => trim($value) === ''));

        if ($empty === []) {
            return $text;
        }

        $pattern = '/([ \t]*)(?:'.implode('|', array_map(static fn (string $p): string => preg_quote($p, '/'), $empty)).')([ \t]*)/';

        return preg_replace_callback(
            $pattern,
            static fn (array $m): string => $m[1] !== '' && $m[2] !== '' ? ' ' : $m[1].$m[2],
            $text
        );
    }

    /**
     * Escape a text for HTML (quotes kept readable) and turn line breaks into <br>.
     */
    public static function toHtml(?string $text): string
    {
        return nl2br(htmlspecialchars((string) $text, ENT_NOQUOTES, 'UTF-8'));
    }

    public static function set(string $key, ?string $value): void
    {
        Setting::setValue($key, trim((string) $value));
    }
}
