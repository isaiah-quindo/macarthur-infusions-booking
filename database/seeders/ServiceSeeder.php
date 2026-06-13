<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Mirrors src/components/Services.tsx in the marketing frontend — same
     * six categories, same item names. Durations and prices are
     * PLACEHOLDERS for the nurse to confirm in /admin/services.
     *
     * Items that read as programs/coordination rather than bookable
     * appointments are seeded INACTIVE (false) so the public page stays a
     * bookable menu; the nurse can activate them any time.
     *
     * Copy is intentionally benefit-led and avoids therapeutic claims
     * (TGA/AHPRA). Phrases like "may support" and "designed to help with"
     * are preferred over "treats" or "cures".
     */
    public function run(): void
    {
        $services = $this->services();

        $slugs = [];

        foreach ($services as $order => $s) {
            $slug = Str::slug($s['name']);
            $slugs[] = $slug;

            Service::updateOrCreate(
                ['slug' => $slug],
                [
                    'category' => $s['category'],
                    'name' => $s['name'],
                    'description' => $s['description'] ?? null,
                    'included' => $s['included'] ?? null,
                    'benefits' => $s['benefits'] ?? null,
                    'faqs' => $s['faqs'] ?? null,
                    'duration_minutes' => $s['duration'],
                    'price_cents' => $s['price'],
                    'is_active' => $s['active'],
                    'display_order' => $order,
                ],
            );
        }

        // Drop services no longer in the canonical list — but never ones
        // that already have bookings.
        Service::whereNotIn('slug', $slugs)
            ->whereDoesntHave('bookings')
            ->delete();
    }

    private function services(): array
    {
        // FAQs many services share — keeps copy consistent across the menu.
        $commonFaqs = [
            'consultation' => [
                'question' => 'Do I need a doctor’s referral?',
                'answer' => 'No referral is required. Every appointment begins with a nurse-led consultation to check your suitability and tailor the treatment to you.',
            ],
            'safety' => [
                'question' => 'Is it safe?',
                'answer' => 'All infusions and injections are administered by qualified, experienced nurses using sterile, single-use equipment in our clinic. We take a thorough history beforehand to confirm the treatment is right for you.',
            ],
            'drive' => [
                'question' => 'Can I drive home afterwards?',
                'answer' => 'Yes — most people feel completely normal or even more energised straight after. We’ll let you know if anything in your appointment requires a short rest before leaving.',
            ],
            'frequency' => [
                'question' => 'How often can I have this?',
                'answer' => 'It depends on your goals and bloodwork. Some people come once for a top-up, others maintain a regular schedule. Your nurse will recommend a cadence after your consultation.',
            ],
        ];

        return [
            // ───────── IV Infusion Therapies ─────────
            [
                'category' => 'IV Infusion Therapies', 'name' => 'Iron Infusions',
                'duration' => 60, 'price' => 24900, 'active' => true,
                'description' => 'A nurse-administered intravenous iron infusion for adults with low iron or iron-deficiency anaemia who can’t tolerate or aren’t responding to oral iron. Delivered slowly under close observation in a comfortable, private setting.',
                'included' => [
                    'Pre-treatment consultation and suitability check',
                    'IV cannulation and iron infusion (Ferinject / Monoferric where clinically appropriate)',
                    'Continuous observation throughout the infusion',
                    'Post-infusion monitoring and aftercare advice',
                ],
                'benefits' => [
                    'May help correct iron deficiency more quickly than oral supplements',
                    'Useful when tablets cause stomach upset or aren’t absorbing well',
                    'Can support energy, concentration and exercise tolerance',
                    'One appointment is often enough — no daily tablets to remember',
                ],
                'faqs' => [
                    ['question' => 'Do I need recent blood tests?', 'answer' => 'Yes — please bring or email recent iron studies (within the last 3 months). If you don’t have results, we can advise on getting them before your appointment.'],
                    $commonFaqs['consultation'],
                    ['question' => 'Are there side effects?', 'answer' => 'Most people tolerate iron infusions well. Mild headache, nausea or temporary skin staining at the IV site can occur. Your nurse will explain everything before and monitor you throughout.'],
                    $commonFaqs['drive'],
                ],
            ],
            [
                'category' => 'IV Infusion Therapies', 'name' => 'Vitamin C Infusions',
                'duration' => 60, 'price' => 19900, 'active' => true,
                'description' => 'A high-dose intravenous vitamin C infusion designed to support immune function, antioxidant defence and overall wellbeing. Doses are tailored to you after a nurse-led consultation.',
                'included' => [
                    'Pre-treatment consultation and dose tailoring',
                    'IV cannulation and vitamin C infusion',
                    'Hydration support during the appointment',
                    'Aftercare advice for the rest of your day',
                ],
                'benefits' => [
                    'Supports immune resilience, particularly through the cooler months',
                    'Contributes to collagen synthesis for skin, joints and connective tissue',
                    'Acts as an antioxidant against everyday oxidative stress',
                    'Bypasses absorption limits of oral vitamin C',
                ],
                'faqs' => [
                    ['question' => 'Why intravenous instead of tablets?', 'answer' => 'Oral vitamin C is capped by what your gut can absorb. Going IV achieves blood levels you simply can’t reach with tablets.'],
                    ['question' => 'Do I need a G6PD test?', 'answer' => 'For higher doses we may screen for G6PD deficiency first. Your nurse will discuss this in your consultation.'],
                    $commonFaqs['safety'], $commonFaqs['frequency'],
                ],
            ],
            [
                'category' => 'IV Infusion Therapies', 'name' => 'Glutathione Infusions',
                'duration' => 45, 'price' => 19900, 'active' => true,
                'description' => 'Glutathione is one of the body’s most important antioxidants. This infusion supports detoxification pathways, skin radiance and overall cellular health, administered by a qualified nurse in our private clinic.',
                'included' => [
                    'Pre-treatment consultation',
                    'IV glutathione infusion at a clinically appropriate dose',
                    'Observation throughout',
                    'Aftercare and skincare guidance',
                ],
                'benefits' => [
                    'Supports the body’s natural detoxification pathways',
                    'May contribute to brighter, more even skin tone',
                    'Antioxidant support against oxidative stress',
                    'Often paired with vitamin C for amplified benefit',
                ],
                'faqs' => [
                    ['question' => 'Can I have it with a vitamin C infusion?', 'answer' => 'Yes — many clients combine the two. Speak with your nurse about whether a same-day or split-day approach suits you.'],
                    $commonFaqs['consultation'], $commonFaqs['drive'], $commonFaqs['frequency'],
                ],
            ],
            [
                'category' => 'IV Infusion Therapies', 'name' => 'NAD+ Therapy',
                'duration' => 120, 'price' => 39900, 'active' => true,
                'description' => 'NAD+ (nicotinamide adenine dinucleotide) is a coenzyme involved in energy production and DNA repair. Levels decline with age. This longer infusion is delivered slowly to maximise comfort and tolerance.',
                'included' => [
                    'Extended consultation to discuss goals',
                    'Slow IV NAD+ infusion (delivered over ~2 hours for comfort)',
                    'Continuous observation and titration',
                    'Hydration and snacks during your appointment',
                ],
                'benefits' => [
                    'Supports cellular energy production',
                    'May contribute to mental clarity and focus',
                    'Often part of a healthy-ageing or recovery protocol',
                    'Done in a calm, comfortable clinic setting',
                ],
                'faqs' => [
                    ['question' => 'Why does NAD+ take so long?', 'answer' => 'Infusing NAD+ too quickly is uncomfortable for most people. Going slow keeps the experience easy and the dose effective.'],
                    ['question' => 'Will I feel it during the infusion?', 'answer' => 'Some people feel a flushing or chest tightness if it runs too fast — we titrate to your comfort and pause whenever needed.'],
                    $commonFaqs['consultation'], $commonFaqs['frequency'],
                ],
            ],
            [
                'category' => 'IV Infusion Therapies', 'name' => "Myers' Cocktail",
                'duration' => 60, 'price' => 22900, 'active' => true,
                'description' => 'A balanced blend of vitamins and minerals — magnesium, calcium, B vitamins and vitamin C — based on the original Myers’ formulation. A reliable choice for general wellness, fatigue or post-illness recovery.',
                'included' => [
                    'Consultation and history review',
                    'IV Myers’ Cocktail infusion',
                    'Observation throughout',
                    'Aftercare advice',
                ],
                'benefits' => [
                    'Broad nutrient top-up in a single appointment',
                    'May support energy, mood and immune function',
                    'A common starting point for IV therapy newcomers',
                    'Tailored adjustments possible based on your needs',
                ],
                'faqs' => [
                    ['question' => 'How is this different from other drips?', 'answer' => 'The Myers’ Cocktail is a well-known, balanced formula. Our other drips lean into a specific outcome (recovery, immunity, beauty), while Myers’ is the all-round option.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'], $commonFaqs['frequency'],
                ],
            ],
            [
                'category' => 'IV Infusion Therapies', 'name' => 'Recovery Drip',
                'duration' => 60, 'price' => 21900, 'active' => true,
                'description' => 'A rehydrating IV blend designed to help you recover faster after travel, intense training, dehydration or a heavy social weekend. Combines fluids, electrolytes and supportive nutrients.',
                'included' => [
                    'Consultation and hydration assessment',
                    'IV fluids with electrolytes and supportive vitamins',
                    'Optional anti-nausea support if clinically appropriate',
                    'Aftercare advice',
                ],
                'benefits' => [
                    'Faster rehydration than drinking water alone',
                    'May ease headache, fatigue and nausea after dehydration',
                    'Restores key electrolytes lost through illness or exertion',
                    'A practical reset before getting back to work or training',
                ],
                'faqs' => [
                    ['question' => 'Will this cure my hangover?', 'answer' => 'We don’t promise that — but rehydrating intravenously is faster than oral fluids and many people feel significantly better afterwards.'],
                    $commonFaqs['drive'], $commonFaqs['safety'], $commonFaqs['frequency'],
                ],
            ],

            // ───────── Wellness & Nutrient Injections ─────────
            [
                'category' => 'Wellness & Nutrient Injections', 'name' => 'Vitamin B12 Injection',
                'duration' => 15, 'price' => 4900, 'active' => true,
                'description' => 'An intramuscular vitamin B12 injection delivered in under fifteen minutes. A practical choice for adults with low B12, vegan/vegetarian diets, or busy schedules who want a fast top-up.',
                'included' => [
                    'Brief consultation and suitability check',
                    'IM B12 injection (hydroxocobalamin)',
                    'Aftercare advice',
                ],
                'benefits' => [
                    'B12 contributes to normal energy metabolism',
                    'Supports normal red blood cell formation',
                    'Helps maintain normal nervous-system function',
                    'In and out in fifteen minutes',
                ],
                'faqs' => [
                    ['question' => 'How often should I have a B12 injection?', 'answer' => 'It depends on your levels and diet — anywhere from weekly to every few months. Your nurse will recommend a cadence after seeing your bloodwork.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'],
                ],
            ],
            [
                'category' => 'Wellness & Nutrient Injections', 'name' => 'Biotin Injection',
                'duration' => 15, 'price' => 4900, 'active' => true,
                'description' => 'An intramuscular biotin (vitamin B7) injection. A popular choice for clients seeking support for hair, skin and nail health alongside good nutrition and skincare.',
                'included' => [
                    'Brief consultation',
                    'IM biotin injection',
                    'Aftercare advice',
                ],
                'benefits' => [
                    'Biotin contributes to the maintenance of normal hair and skin',
                    'Supports normal energy metabolism',
                    'A quick, no-fuss appointment',
                    'Complements oral supplementation',
                ],
                'faqs' => [
                    ['question' => 'How quickly will I see results?', 'answer' => 'Hair and nail changes are slow by nature — give it weeks to months alongside consistent nutrition and skincare. Results vary.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'],
                ],
            ],
            [
                'category' => 'Wellness & Nutrient Injections', 'name' => 'Vitamin C Injection',
                'duration' => 15, 'price' => 4900, 'active' => true,
                'description' => 'An intramuscular vitamin C injection — a faster alternative to the full IV infusion when you want a smaller dose without committing to an hour in the chair.',
                'included' => [
                    'Brief consultation',
                    'IM vitamin C injection',
                    'Aftercare advice',
                ],
                'benefits' => [
                    'Supports normal immune function',
                    'Contributes to normal collagen formation',
                    'Antioxidant support',
                    'Fast, low-commitment appointment',
                ],
                'faqs' => [
                    ['question' => 'Should I get the injection or the IV?', 'answer' => 'The IV delivers a much larger dose and is the go-to if you’re after the full antioxidant effect. The injection is great for a quick top-up.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'],
                ],
            ],

            // ───────── Women's Health & Healthy Ageing ─────────
            [
                'category' => "Women's Health & Healthy Ageing", 'name' => 'Perimenopause & Menopause Support Infusions',
                'duration' => 60, 'price' => 21900, 'active' => true,
                'description' => 'A structured IV appointment tailored for perimenopause and menopause — nutrients selected to support energy, mood, sleep and overall wellbeing, alongside your broader care plan with your GP.',
                'included' => [
                    'Extended consultation with our women’s-health-trained nurse',
                    'IV menopause-support infusion',
                    'Aftercare and follow-up planning',
                ],
                'benefits' => [
                    'A safe, woman-led space to talk openly',
                    'Nutritional support that complements HRT and lifestyle change',
                    'Magnesium and B vitamins for muscle and nervous-system support',
                    'A planned hour to focus on your wellbeing',
                ],
                'faqs' => [
                    ['question' => 'Should I see a doctor about HRT too?', 'answer' => 'If you haven’t already, yes — we’re a complement to medical care, not a replacement. Our nurse can suggest GPs experienced in menopause care if you need a referral.'],
                    $commonFaqs['consultation'], $commonFaqs['frequency'], $commonFaqs['safety'],
                ],
            ],

            // ───────── Recovery & Performance ─────────
            [
                'category' => 'Recovery & Performance', 'name' => 'Athletic Recovery Infusions',
                'duration' => 60, 'price' => 21900, 'active' => true,
                'description' => 'An IV blend developed with active adults in mind — hydration, amino acids, antioxidants and B vitamins to support recovery between hard training sessions or after events.',
                'included' => [
                    'Pre-treatment consultation including training context',
                    'IV athletic-recovery blend',
                    'Observation and aftercare',
                ],
                'benefits' => [
                    'Supports muscle recovery and rehydration',
                    'Antioxidant support after high-intensity sessions',
                    'A planned recovery tool around events and competitions',
                ],
                'faqs' => [
                    ['question' => 'Is this allowed in competition?', 'answer' => 'Many components are everyday vitamins, but anti-doping rules differ by sport. Always check with your sport’s anti-doping body before competing.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'], $commonFaqs['frequency'],
                ],
            ],
            [
                'category' => 'Recovery & Performance', 'name' => 'Muscle Recovery Support',
                'duration' => 60, 'price' => 21900, 'active' => false,
                'description' => 'A muscle-focused IV appointment combining magnesium, amino acids and electrolytes — useful in the day or two after a heavy session, race or long event.',
                'included' => [
                    'Consultation and soreness assessment',
                    'IV muscle-recovery blend',
                    'Aftercare advice',
                ],
                'benefits' => [
                    'Magnesium supports normal muscle function',
                    'Amino acids contribute to recovery',
                    'Hydration support',
                ],
                'faqs' => [
                    ['question' => 'Will this fix an injury?', 'answer' => 'No — soft-tissue injuries need physiotherapy and time. This appointment supports recovery from training load, not injury treatment.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'],
                ],
            ],
            [
                'category' => 'Recovery & Performance', 'name' => 'Immune System Support',
                'duration' => 60, 'price' => 19900, 'active' => false,
                'description' => 'An immune-focused program for active adults — combining IV immunity infusions with practical nutrition and recovery guidance to help you stay healthy through demanding periods.',
                'included' => [
                    'Initial consultation and immunity history',
                    'IV immune-support infusion',
                    'Nutrition and recovery guidance',
                    'Optional repeat scheduling before key events or travel',
                ],
                'benefits' => [
                    'Supports normal immune function during high-load periods',
                    'Useful pre-travel or before major events',
                    'A planned, proactive approach',
                ],
                'faqs' => [
                    ['question' => 'Should I come if I’m already sick?', 'answer' => 'Please call us first — if you have an active fever or infection we may ask you to reschedule.'],
                    $commonFaqs['consultation'], $commonFaqs['safety'],
                ],
            ],

            // ───────── Beauty & Skin Health ─────────
            [
                'category' => 'Beauty & Skin Health', 'name' => 'Glutathione Therapy',
                'duration' => 45, 'price' => 19900, 'active' => true,
                'description' => 'A skin-focused IV glutathione appointment — useful as a standalone treatment or as part of a wider skincare routine. Delivered by a qualified nurse in our private clinic.',
                'included' => [
                    'Pre-treatment consultation',
                    'IV glutathione at a clinically appropriate dose',
                    'Aftercare and skincare guidance',
                ],
                'benefits' => [
                    'Antioxidant support against everyday oxidative stress',
                    'May contribute to a brighter, more even tone',
                    'Complements your skincare and dermatology care',
                ],
                'faqs' => [
                    ['question' => 'Will this lighten my skin?', 'answer' => 'We don’t market glutathione for skin lightening. Many clients notice a more even, brighter tone over time, but individual results vary.'],
                    $commonFaqs['consultation'], $commonFaqs['frequency'], $commonFaqs['safety'],
                ],
            ],
            [
                'category' => 'Beauty & Skin Health', 'name' => 'Vitamin C Therapy',
                'duration' => 45, 'price' => 19900, 'active' => true,
                'description' => 'An IV vitamin C appointment focused on skin health — supporting collagen-related processes, antioxidant defence and overall radiance.',
                'included' => [
                    'Pre-treatment consultation',
                    'IV vitamin C',
                    'Aftercare and skincare guidance',
                ],
                'benefits' => [
                    'Contributes to normal collagen formation',
                    'Antioxidant support',
                    'Pairs well with glutathione for an amplified effect',
                ],
                'faqs' => [
                    ['question' => 'Can I combine this with the Glutathione Therapy?', 'answer' => 'Yes — many clients alternate or combine the two. Speak with your nurse about a schedule.'],
                    $commonFaqs['consultation'], $commonFaqs['frequency'], $commonFaqs['safety'],
                ],
            ],

        ];
    }
}
