/**
 * TipTap pro znalostní bázi.
 *
 * Balíček `nyoncode/laravel-knowledge-base` schválně TipTap nezabaluje —
 * nacpal by do aplikací druhou kopii ProseMirroru a připnul jim verzi. Místo
 * toho čeká na `window.kbEditor(el, options)` a dokud ho nenajde, textový
 * ovladač se v editoru vůbec nenabídne.
 *
 * ⚠️ Přidání téhle položky do `vite.config.js` znamená **restart
 * `npm run dev`** — běžící dev server nový entry nezná a stránka editoru pak
 * zůstane bez JavaScriptu (viz `.ai/rules/resources.md`).
 */

import { Editor, Extension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import TextStyle from '@tiptap/extension-text-style';
import FontFamily from '@tiptap/extension-font-family';
import Highlight from '@tiptap/extension-highlight';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableHeader from '@tiptap/extension-table-header';
import TableCell from '@tiptap/extension-table-cell';
import Typography from '@tiptap/extension-typography';
import FocusClasses from '@tiptap/extension-focus';
import UniqueID from '@tiptap/extension-unique-id';
import InvisibleCharacters from '@tiptap/extension-invisible-characters';
import FileHandler from '@tiptap/extension-file-handler';
import FloatingMenu from '@tiptap/extension-floating-menu';
import DragHandle from '@tiptap/extension-drag-handle';
import Mention from '@tiptap/extension-mention';
import Suggestion from '@tiptap/suggestion';
import { createLowlight } from 'lowlight';

import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import diff from 'highlight.js/lib/languages/diff';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import markdown from 'highlight.js/lib/languages/markdown';
import php from 'highlight.js/lib/languages/php';
import python from 'highlight.js/lib/languages/python';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';

const lowlight = createLowlight();

// Sada odpovídá nabídce `knowledge-base.editors.languages`. Jazyk, který
// lowlight nezná, se v editoru vykreslí černobíle — hotová stránka ho přitom
// obarví (tam zvýrazňuje Torchlight), takže autor vidí chybu, která není.
// `html` a `blade` jedou na gramatice `xml`, `typescript` pokrývá i TS v JS.
lowlight.register({
    bash,
    css,
    diff,
    html: xml,
    blade: xml,
    javascript,
    json,
    markdown,
    php,
    python,
    sql,
    typescript,
    xml,
    yaml,
});

/**
 * Blok kódu, který si nese **jazyk a název souboru až do HTML**.
 *
 * TipTap ukládá jazyk jen do třídy na `<code>` a název souboru nezná vůbec.
 * Zvýrazňovač na serveru ale staví hlavičku bloku z `data-*` na `<pre>`
 * (`App\Support\Highlighting\CodeBlockPipeline`) — bez tohohle by ukázka
 * z rich-textového editoru přišla o titulek, který do ní autor napsal, a
 * vypadala jinak než tatáž ukázka z blokového editoru.
 */
const CodeBlockWithTitle = CodeBlockLowlight.extend({
    addAttributes() {
        return {
            ...this.parent?.(),

            language: {
                default: null,
                // Čte se z třídy na `<code>`, protože tam ji píše samotný
                // CodeBlock; `data-lang` je jen záloha pro HTML odjinud.
                parseHTML: (element) => {
                    const classes =
                        element.querySelector('code')?.getAttribute('class') ?? '';
                    const matched = classes.match(/language-([\w#+._-]+)/);

                    return matched ? matched[1] : element.getAttribute('data-lang');
                },
                renderHTML: (attributes) =>
                    attributes.language ? { 'data-lang': attributes.language } : {},
            },

            title: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-title'),
                renderHTML: (attributes) =>
                    attributes.title ? { 'data-title': attributes.title } : {},
            },
        };
    },
});

/**
 * Seznam emoji – dotažený až ve chvíli, kdy o něj někdo požádá.
 *
 * Osmnáct set emoji je 466 kB, tedy víc než celý zbytek editoru. Načíst je
 * do každé stránky s editorem kvůli našeptávači, který většina autorů nikdy
 * nevyvolá, je špatný obchod; import se proto spustí na první dvojtečce.
 * Než dorazí, nabídka je prázdná — vzhledem k tomu, že se čeká na dva znaky
 * za `:`, se to v praxi nestihne projevit.
 */
let emojiData = [];
let emojiRequest = null;

function emojiCatalogue() {
    if (emojiRequest === null) {
        emojiRequest = import('@tiptap/extension-emoji')
            .then((module) => {
                emojiData = module.gitHubEmojis;
            })
            .catch(() => {
                emojiData = [];
            });
    }

    return emojiData;
}

/** Obrázky, které se dají pustit nebo vložit rovnou do textu. */
const IMAGE_TYPES = [
    'image/png',
    'image/jpeg',
    'image/gif',
    'image/webp',
    'image/svg+xml',
];

/**
 * Vlastnost `textStyle` jako lokální rozšíření.
 *
 * Barva i velikost písma jsou v TipTapu jen atribut nad `textStyle` a oficiální
 * balíčky kolem toho jsou pár řádků. Píšeme si je sami, protože přibrat kvůli
 * nim dvě npm závislosti do aplikace, která už `text-style` má, je horší obchod
 * než třicet řádků tady. (Rodinu písma naopak bereme hotovou — ta řeší i
 * uvozovkování názvů, na kterém se dá snadno seknout.)
 *
 * @param {string} name       jméno rozšíření a příkazů (`setColor`, `unsetColor`)
 * @param {string} attribute  jméno atributu v dokumentu
 * @param {string} property   CSS vlastnost, do které se vykreslí
 */
function textStyleAttribute(name, attribute, property) {
    const capitalized = name.charAt(0).toUpperCase() + name.slice(1);

    return Extension.create({
        name,

        addOptions() {
            return { types: ['textStyle'] };
        },

        addGlobalAttributes() {
            return [
                {
                    types: this.options.types,
                    attributes: {
                        [attribute]: {
                            default: null,
                            parseHTML: (element) => element.style[property] || null,
                            renderHTML: (attributes) =>
                                attributes[attribute]
                                    ? { style: `${property}: ${attributes[attribute]}` }
                                    : {},
                        },
                    },
                },
            ];
        },

        addCommands() {
            return {
                [`set${capitalized}`]:
                    (value) =>
                    ({ chain }) =>
                        chain().setMark('textStyle', { [attribute]: value }).run(),

                [`unset${capitalized}`]:
                    () =>
                    ({ chain }) =>
                        chain()
                            .setMark('textStyle', { [attribute]: null })
                            // Bez úklidu zůstane v dokumentu prázdný `<span>`,
                            // který se při dalším psaní chová jako past.
                            .removeEmptyTextStyle()
                            .run(),
            };
        },
    });
}

const TextColor = textStyleAttribute('textColor', 'color', 'color');
const FontSize = textStyleAttribute('fontSize', 'fontSize', 'fontSize');

/**
 * Našeptávač pro `@` a `:` — jeden pro obojí.
 *
 * TipTap dodává jen mechaniku (kdy se spustil, co se píše za znakem); seznam
 * pod kurzorem je na nás. Píšeme ho ručně místo tippy.js, protože potřebuje
 * jen „drž se u kurzoru" a klávesnici, kdežto tippy by přidal celý poziční
 * engine kvůli jednomu obdélníku.
 *
 * @param {(item: any) => string} label  co se ukáže v řádku nabídky
 */
function suggestionList(label) {
    return () => {
        let panel = null;
        let items = [];
        let index = 0;
        let pick = () => {};

        const paint = () => {
            if (! panel) {
                return;
            }

            panel.innerHTML = '';

            items.forEach((item, position) => {
                const row = document.createElement('button');

                row.type = 'button';
                row.className = 'kb-suggest-item';
                row.textContent = label(item);

                if (position === index) {
                    row.dataset.active = 'true';
                }

                // `mousedown`, ne `click`: klik by nejdřív sebral fokus
                // editoru a vložení by skončilo mimo dokument.
                row.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    pick(item);
                });

                panel.appendChild(row);
            });
        };

        const place = (rect) => {
            if (! panel || ! rect) {
                return;
            }

            panel.style.left = `${rect.left + window.scrollX}px`;
            panel.style.top = `${rect.bottom + window.scrollY + 4}px`;
        };

        return {
            onStart(props) {
                items = props.items;
                index = 0;
                pick = (item) => props.command(item);

                panel = document.createElement('div');
                panel.className = 'kb-suggest';
                document.body.appendChild(panel);

                paint();
                place(props.clientRect?.());
            },

            onUpdate(props) {
                items = props.items;
                index = 0;
                pick = (item) => props.command(item);

                paint();
                place(props.clientRect?.());
            },

            onKeyDown({ event }) {
                if (event.key === 'Escape') {
                    panel?.remove();
                    panel = null;

                    return true;
                }

                if (items.length === 0) {
                    return false;
                }

                if (event.key === 'ArrowDown') {
                    index = (index + 1) % items.length;
                    paint();

                    return true;
                }

                if (event.key === 'ArrowUp') {
                    index = (index - 1 + items.length) % items.length;
                    paint();

                    return true;
                }

                if (event.key === 'Enter' || event.key === 'Tab') {
                    pick(items[index]);

                    return true;
                }

                return false;
            },

            onExit() {
                panel?.remove();
                panel = null;
            },
        };
    };
}

/**
 * Emoji pod `:`.
 *
 * Schválně **není** uzel `emoji` z oficiálního rozšíření: ten by do článku
 * uložil `<span data-type="emoji">`, který by sanitizér stejně zahodil, a
 * čtenář nepotřebuje nic víc než samotný znak. Bereme z něj jen seznam.
 */
const EmojiSuggest = Extension.create({
    name: 'emojiSuggest',

    addProseMirrorPlugins() {
        return [
            Suggestion({
                editor: this.editor,
                char: ':',
                // Dvě písmena, jinak by nabídka vyskakovala uprostřed `http://`
                // a za každou dvojtečkou ve větě.
                startOfLine: false,
                allowSpaces: false,
                items: ({ query }) => {
                    const catalogue = emojiCatalogue();

                    return query.length < 2
                        ? []
                        : catalogue
                            .filter((emoji) =>
                                emoji.shortcodes.some((code) => code.startsWith(query))
                            )
                            .slice(0, 8);
                },
                command: ({ editor, range, props }) =>
                    editor
                        .chain()
                        .focus()
                        .insertContentAt(range, `${props.emoji} `)
                        .run(),
                render: suggestionList((item) => `${item.emoji}  :${item.shortcodes[0]}:`),
            }),
        ];
    },
});

/**
 * Nahraje soubory a vloží je jako obrázky.
 *
 * Po jednom, ne najednou: hostitel nahrává přes jednu Livewire vlastnost a
 * dva souběžné uploady by si přepsaly odpověď.
 */
async function insertFiles(editor, files, position, upload) {
    if (typeof upload !== 'function') {
        return;
    }

    for (const file of files) {
        try {
            const src = await upload(file);

            if (! src) {
                continue;
            }

            position === null || position === undefined
                ? editor.chain().focus().setImage({ src }).run()
                : editor.chain().focus().insertContentAt(position, {
                    type: 'image',
                    attrs: { src },
                }).run();
        } catch (error) {
            // Nahrávání hlásí chybu hostitel (proužek nad plochou); tady by
            // druhá hláška jen překryla tu první.
            break;
        }
    }
}

/**
 * @param {HTMLElement} element  kam se editor namontuje
 * @param {{
 *   content?: string,
 *   onChange?: (html: string) => void,
 *   compact?: boolean,
 *   floating?: HTMLElement|null,
 *   handle?: HTMLElement|null,
 *   upload?: ((file: File) => Promise<string>)|null,
 *   mentions?: Array<{label: string, url: string}>,
 *   placeholder?: string,
 * }} options
 * @returns {Editor}
 */
window.kbEditor = function (element, options = {}) {
    const {
        content = '',
        onChange = () => {},
        compact = false,
        floating = null,
        handle = null,
        upload = null,
        mentions = [],
        placeholder = 'Piš…',
    } = options;

    const extensions = [
        // Vlastní blok kódu má přednost před tím ze StarterKitu, jinak by
        // se uzel `codeBlock` registroval dvakrát.
        StarterKit.configure({ codeBlock: false }),
        Link.configure({ openOnClick: false, autolink: true }),
        Image,
        Placeholder.configure({ placeholder }),
        CodeBlockWithTitle.configure({ lowlight }),
        Underline,
        Subscript,
        Superscript,
        TextStyle,
        TextColor,
        FontSize,
        FontFamily,
        // `multicolor`: podbarvení nese vlastní barvu, ne jednu žlutou pro
        // všechno — jinak nejde odlišit „zvýrazněno" od „pozor".
        Highlight.configure({ multicolor: true }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        TaskList,
        // `nested`: úkol pod úkolem je běžný tvar kontrolního seznamu
        // („nasadit → z toho tři kroky"), a bez toho by šel udělat jen
        // plochý výčet.
        TaskItem.configure({ nested: true }),
        Table.configure({ resizable: true }),
        TableRow,
        TableHeader,
        TableCell,
        // Typografie česky: výchozí sada dělá anglické “uvozovky“, což je
        // v českém textu prostě chyba.
        Typography.configure({
            openDoubleQuote: '„',
            closeDoubleQuote: '“',
            openSingleQuote: '‚',
            closeSingleQuote: '‘',
        }),
        FocusClasses.configure({ className: 'kb-focused', mode: 'shallowest' }),
        // Jen nadpisy a odstavce: identita se ukládá do dokumentu, takže
        // rozdávat ji i buňkám tabulky by článek nafouklo bez užitku.
        UniqueID.configure({ types: ['heading', 'paragraph'] }),
        InvisibleCharacters.configure({ visible: false }),
        EmojiSuggest,
    ];

    if (typeof upload === 'function') {
        extensions.push(
            FileHandler.configure({
                allowedMimeTypes: IMAGE_TYPES,
                onDrop: (editor, files, position) =>
                    insertFiles(editor, files, position, upload),
                onPaste: (editor, files) =>
                    insertFiles(editor, files, null, upload),
            })
        );
    }

    if (mentions.length > 0) {
        extensions.push(
            Mention.configure({
                suggestion: {
                    char: '@',
                    items: ({ query }) =>
                        mentions
                            .filter((item) =>
                                item.label.toLowerCase().includes(query.toLowerCase())
                            )
                            .slice(0, 8),
                    render: suggestionList((item) => item.label),
                    // Vloží **odkaz**, ne uzel `mention`. Zmínka je způsob,
                    // jak odkaz najít, ne zvláštní druh obsahu: uložený
                    // článek tak nese obyčejné `<a>`, které umí přečíst
                    // i čtenář, sanitizér i vyhledávání.
                    command: ({ editor, range, props }) =>
                        editor
                            .chain()
                            .focus()
                            .insertContentAt(range, [
                                {
                                    type: 'text',
                                    text: props.label,
                                    marks: [{ type: 'link', attrs: { href: props.url } }],
                                },
                                { type: 'text', text: ' ' },
                            ])
                            .run(),
                },
            })
        );
    }

    // Plovoucí nabídka a úchyt patří celé stránce. V bloku by úchyt soupeřil
    // s přetahováním samotných bloků a nabídka vložení s jejich paletou.
    if (floating && ! compact) {
        extensions.push(FloatingMenu.configure({ element: floating }));
    }

    if (handle && ! compact) {
        extensions.push(DragHandle.configure({ render: () => handle }));
    }

    return new Editor({
        element,
        content,
        extensions,
        editorProps: {
            attributes: { class: 'focus:outline-none' },
        },
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
    });
};
