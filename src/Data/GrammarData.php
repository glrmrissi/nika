<?php

namespace App\Data;

class GrammarData
{
    public static function getAll(): array
    {
        return [
            // は (wa) - Topic Marker
            ['は', 'wa', 'Topic Marker', 'Marks the topic or theme of a sentence. Indicates what the sentence is about.',
             'Place after the subject/topic. Can be contrastive. Replaces が and を when the subject/object is the topic. In negative sentences, often contrasts with what is not.',
             '私は学生です。', 'Watashi wa gakusei desu.', 'I am a student.',
             '猫はどこですか？', 'Neko wa doko desu ka?', 'Where is the cat?',
             'case', 1],

            // が (ga) - Subject Marker
            ['が', 'ga', 'Subject Marker', 'Marks the grammatical subject of a sentence. Used with existence verbs and adjectives of desire/ability.',
             'Use when introducing new information or with 好き, 上手, 欲しい, できる. Answers "who/what did it?". Never used for the topic — use は for topic, が for subject.',
             '猫が好きです。', 'Neko ga suki desu.', 'I like cats.',
             '誰が来ましたか？', 'Dare ga kimashita ka?', 'Who came?',
             'case', 2],

            // を (o) - Object Marker
            ['を', 'o', 'Object Marker', 'Marks the direct object of a transitive verb.',
             'Place directly before the verb. With motion verbs (歩く, 出る, 飛ぶ) marks the space traversed. Never used with いる/ある or intransitive verbs.',
             '本を読みます。', 'Hon o yomimasu.', 'I read a book.',
             '部屋を出ました。', 'Heya o demashita.', 'I left the room.',
             'case', 3],

            // に (ni) - Target/Direction
            ['に', 'ni', 'Target / Direction', 'Indicates direction, indirect object, specific time, purpose, or agent.',
             'Use for destination with movement verbs (行く, 来る, 帰る), for "at [time]", and for "to/for [person]". Also marks the agent in passive sentences and the result after なる.',
             '学校に行きます。', 'Gakkou ni ikimasu.', 'I go to school.',
             '友達に手紙を書きました。', 'Tomodachi ni tegami o kakimashita.', 'I wrote a letter to a friend.',
             'case', 4],

            // へ (e) - Direction
            ['へ', 'e', 'Direction Marker', 'Indicates the direction of movement. Emphasizes direction over destination.',
             'Read as "e" not "he". Interchangeable with に for destinations, but sounds more literary. Common in formal letters and signposts.',
             '東京へ行きます。', 'Toukyou e ikimasu.', 'I am going to Tokyo.',
             'こちらへどうぞ。', 'Kochira e douzo.', 'This way, please.',
             'case', 5],

            // で (de) - Location/Means
            ['で', 'de', 'Location / Means', 'Indicates location of action, means, material, cause, or scope.',
             'Place after the location/means noun, before the verb. NOT for existence (use に with いる/ある). Think of it as "at/by/with" depending on context.',
             '図書館で勉強します。', 'Toshokan de benkyou shimasu.', 'I study at the library.',
             'バスで帰ります。', 'Basu de kaerimasu.', 'I will go home by bus.',
             'case', 6],

            // と (to) - Together/Quotation
            ['と', 'to', 'Together / Quotation', 'Marks a companion or introduces direct/indirect quotations.',
             'For "with someone": Xと = "together with X". For quotation: place after the quoted speech before 言う/思う/聞く. Exhaustive listing — use や for non-exhaustive.',
             '友達と映画を見ました。', 'Tomodachi to eiga o mimashita.', 'I watched a movie with a friend.',
             '「はい」と答えました。', '"Hai" to kotaemashita.', 'I answered "yes".',
             'case', 7],

            // の (no) - Possession
            ['の', 'no', 'Possession / Modifier', 'Indicates possession, modifies nouns, and creates noun phrases.',
             'Place between two nouns: AのB = "B of A". Can chain multiple の (私の友達の猫). Also nominalizes clauses: 走るのが好き = "like running". Replaces が in relative clauses.',
             '私の本です。', 'Watashi no hon desu.', 'It is my book.',
             '日本語の先生は親切です。', 'Nihongo no sensei wa shinsetsu desu.', 'The Japanese teacher is kind.',
             'case', 8],

            // も (mo) - Also/Too
            ['も', 'mo', 'Also / Too', 'Means "also", "too", or "as well". Replaces は/が/を.',
             'Replace the topic/subject/object particle with も. For "both A and B": AもBも. For "neither A nor B": use negative verb at the end.',
             '私も学生です。', 'Watashi mo gakusei desu.', 'I am also a student.',
             '猫も犬も好きです。', 'Neko mo inu mo suki desu.', 'I like both cats and dogs.',
             'focus', 9],

            // か (ka) - Question
            ['か', 'ka', 'Question / Or', 'Marks a question or expresses "or" between nouns.',
             'For questions: add at the end of sentence (no question mark needed in formal writing). For "or": コーヒーか紅茶 = "coffee or tea". With question words: 誰か = "someone", 何か = "something".',
             '学生ですか？', 'Gakusei desu ka?', 'Are you a student?',
             'コーヒーか紅茶をください。', 'Kouhii ka koucha o kudasai.', 'Please give me coffee or tea.',
             'sentence-final', 10],

            // から (kara) - From/Because
            ['から', 'kara', 'From / Because', 'Indicates starting point (time/space) or reason.',
             'For "from": place after the starting point. For "because": place after the reason clause (verb/い-adj plain form + から). The main clause comes second.',
             '駅から歩きました。', 'Eki kara arukimashita.', 'I walked from the station.',
             '忙しいから行けません。', 'Isogashii kara ikemasen.', 'Because I am busy, I cannot go.',
             'case', 11],

            // まで (made) - Until
            ['まで', 'made', 'Until / Up to', 'Indicates the end point or limit in time or space.',
             'Use with から for "from...until": 九時から五時まで. Can also mean "even": 子供まで = "even children".',
             '九時まで働きます。', 'Kuji made hatarakimasu.', 'I work until 9 o\'clock.',
             '東京までどのくらいですか？', 'Toukyou made dono kurai desu ka?', 'How long until Tokyo?',
             'case', 12],

            // より (yori) - Than
            ['より', 'yori', 'Than / From', 'Marks the standard of comparison. Also used as formal "from".',
             'For comparisons: AのほうがBより = "A is more than B". Can omit のほうが for simple statements. In formal writing, replaces から.',
             '日本語より英語の方が簡単です。', 'Nihongo yori eigo no hou ga kantan desu.', 'English is easier than Japanese.',
             '彼は私より背が高いです。', 'Kare wa watashi yori se ga takai desu.', 'He is taller than me.',
             'case', 13],

            // や (ya) - And (listing)
            ['や', 'ya', 'And (partial listing)', 'Lists multiple items non-exhaustively.',
             'Use when there are more items not listed: AやB. Often paired with など (nado) = "etc." For complete lists, use と.',
             '本や雑誌を読んでいます。', 'Hon ya zasshi o yondeimasu.', 'I am reading books, magazines, etc.',
             '机の上にペンや紙があります。', 'Tsukue no ue ni pen ya kami ga arimasu.', 'There are pens, paper, etc. on the desk.',
             'conjunctive', 14],

            // ね (ne) - Confirmation
            ['ね', 'ne', 'Confirmation / Softener', 'Used to seek agreement, confirmation, or soften statements.',
             'Place at the end of a sentence. Equivalent to "right?", "isn\'t it?", "you know?". Makes speech friendly and natural.',
             'いい天気ですね。', 'Ii tenki desu ne.', 'Nice weather, isn\'t it?',
             'あなたは日本人ですね？', 'Anata wa nihonjin desu ne?', 'You are Japanese, right?',
             'sentence-final', 15],

            // よ (yo) - Emphasis
            ['よ', 'yo', 'Emphasis / Assertion', 'Used to assert information or inform the listener of something new.',
             'Place at the end of a sentence. Implies "I am telling you" or "you should know". Can sound pushy if overused with superiors.',
             '電話がありますよ。', 'Denwa ga arimasu yo.', 'There is a phone (I tell you).',
             'それ、間違っていますよ。', 'Sore, machigatteimasu yo.', 'That is wrong (let me tell you).',
             'sentence-final', 16],
        ];
    }
}
