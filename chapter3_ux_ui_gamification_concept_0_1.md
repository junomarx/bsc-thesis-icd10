# Chapter 3 UX/UI and Gameful Interaction Concept

**Concept ID:** `UXBASE-0.1`  
**Status:** accepted forward design; immediate feedback/completion review are core interaction requirements, visual/gameful refinement is a pre-freeze stretch goal  
**Date:** 8 August 2026  
**Interface clarification:** 9 August 2026, `APIBASE-0.1`  
**Upstream:** Chapter 2 design principles `DP-F1`--`DP-F6`, `PATIENTBASE-0.1`, `QUESTIONBASE-0.1`, `MODELBASE-0.2`, `RULEBASE-0.2`, requirements forward revision 0.7, `APIBASE-0.1`  
**Does not modify:** catalogue/source truth, question/option membership, classification semantics, rule precedence, candidate `RCBASE-0.3`, or the technical evaluation claim

## 1. Purpose and boundary

The learner-facing redesign must correct two different problems without conflating them. First, the interaction requires an explicit pedagogical lifecycle: a learner submits one response, sees the consequence and explanation of that decision, continues through the patient, and can review the completed patient encounter. This is part of the core feedback concept rather than optional decoration. Second, once that functional workflow exists, a bounded UX/UI stretch iteration should make the demonstrator feel intentionally designed, self-explanatory and suitable for live presentation.

The stretch goal does **not** convert the bachelor thesis into a usability or gamification study. No learner experiment, satisfaction measure, engagement metric or learning-effect inference follows from implementing a more polished interface. Chapter 3 may report the redesign as a formative artefact iteration and describe the resulting interface; Results may report technical UI/E2E conformance only.

The concept deliberately favours a professional **coding-case session** over an arcade metaphor. The visual language may be engaging and gameful, but it should remain compatible with deliberate medical-coding work.

## 2. Evidence/design basis

The concept is bounded by evidence already used in Chapter 2:

- Shute and Hattie/Timperley support response-specific, task-focused feedback that goes beyond a bare verdict; the current `DP-F2`--`DP-F5` already carry those implications forward.
- CODIFICO is directly relevant because it combines ICD coding, cases, immediate response cues and game mechanics. Its experience also warns against allowing the game construct to displace the intended ICD-coding construct.
- Gentry et al. (`Gentry2019SeriousGaming`) treat serious gaming/gamification in health-professions education cautiously: the evidence base they reviewed was heterogeneous and mostly low quality, and game elements can distract from rather than facilitate the learning objective. The present work therefore uses gameful mechanics as interaction/presentation choices, not evidence of effectiveness.
- Accessibility implementation should be informed by the current W3C WCAG 2.2 guidance, including visible/non-obscured focus and minimum target-size considerations. `UXBASE-0.1` does not claim WCAG conformance unless the final implementation is actually audited against the relevant criteria. Official overview: <https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/>.

No new broad gamification theory section is required in Chapter 2. If the Chapter 2 prose is reopened, one short bounded paragraph in Section 2.2 and/or one additional design implication is sufficient to explain why gameful presentation remains subordinate to the coding/feedback objective.

## 3. Learner playthrough contract

The core flow is fixed as:

`orientation -> patient selection -> patient dossier -> randomized question -> submit -> immediate feedback -> next question -> patient review -> replay/another patient`

### 3.1 Orientation

The landing view should explain the application without requiring an oral briefing. It should state, in compact form:

1. this is an educational Austrian ICD-10 coding demonstrator;
2. the learner chooses a synthetic patient;
3. each patient contains several independent coding tasks;
4. one response is submitted per task;
5. the system returns `correct`, `suboptimal`, or `incorrect` with an explanation; and
6. it is not a diagnostic or clinical decision-support tool.

A compact three-class legend should make `suboptimal` intelligible before the first question. It must not imply that the three classes form an interval scale.

### 3.2 Patient selection and dossier

The six patients should be presented as distinct **case files**, not as anonymous `CASE-*` identifiers. Each card may show display name, age, question count, neutral complexity label (`foundational` or `involved`) and current-session completion state. Difficulty is informative rather than a lock: all patients remain selectable.

On entering a patient, the learner receives the persistent patient summary/context before the first task. The same information remains reopenable throughout the playthrough without resetting the active question. Information-source distinctions already encoded by the model should remain visible where useful, especially for the unconscious patient where examination/record information must not be mislabeled as anamnesis.

### 3.3 Question state

The question view should show:

- patient identity and a route back to the dossier;
- progress such as `Question 3 of 6` plus a visual progress indicator;
- the atomic coding prompt plus the learner-visible patient context needed for orientation; raw evaluator `question_fact` rows are not a pre-submission API payload in the present baseline;
- three or four fixed ICD-code choices, each with code and designation;
- the dedicated `none_of_above` response; and
- one primary `Submit answer` action.

Question order is randomized per playthrough. Option membership is never randomized. Code-option order may be permuted for presentation, but `none_of_above` may remain visually anchored after the code choices for clarity. Evaluation uses stable identities, never displayed position.

### 3.4 Immediate feedback state

After a successful evaluation the submitted answer becomes read-only for the current playthrough. The learner sees the result **before** continuing.

Every classified feedback panel contains, in this hierarchy:

1. classification label: `Correct`, `Suboptimal`, or `Incorrect`;
2. the submitted code/response;
3. a concise explanation of why the represented facts/rule yield that result;
4. the reference/improvement code or corrective direction where the model defines one; and
5. an optional technical-details disclosure containing criterion, determining rule and other trace elements useful for inspection/demonstration.

Colour may reinforce classification but never carry it alone. The label/icon/explanation must remain sufficient without colour perception. `not_evaluated` is a technical/scope state and must not be presented to a learner as an `incorrect` coding judgement.

The primary action after feedback is `Next question` or, on the final task, `Review patient`.

### 3.5 Patient-completion review

The final view provides closure without manufacturing an unsupported numerical scale. It displays:

- patient identity and `Patient completed` state;
- raw counts of `correct`, `suboptimal`, and `incorrect` outcomes;
- a per-question row/card containing the task label, submitted response and returned class;
- read-only access to the explanation/reference information already returned for that question;
- `Play again` and `Choose another patient` actions.

There is no weighted `2/1/0`, percentage score, letter grade or claim of mastery. `Suboptimal` is a semantically distinct coding judgement, not mathematically half of `correct`.

## 4. Gameful mechanics

Gameful elements are included only where they reinforce orientation, progression, consequence or replay.

| Mechanic | Priority | Intended use | Constraint |
|---|---|---|---|
| Patient case-file roster | Must | Give the six cases identity and a clear starting choice | Do not expose hidden evaluator/oracle information |
| Foundation/involved complexity cue | Must | Signal that some patients require more deliberation | Informational only; no hard locking or claim of validated difficulty |
| Question progress indicator | Must | Make variable 3/5/6-question sessions legible | Derived from actual question collection, never hard-coded |
| Immediate result transition | Must | Give each decision an observable consequence | Uses evaluator output unchanged |
| Patient-completed state | Must | Provide a clear session goal and closure | Completion means questions answered, not educational mastery |
| Session-local patient completion marks | Should | Make progress across the six patients visible | No account/history database required |
| Replay with reshuffled presentation | Must | Add replayability and demonstrate non-hardcoded ordering | Membership and semantic truth remain fixed |
| Restrained completion animation | Could | Make successful completion feel intentional | Cosmetic only; respect `prefers-reduced-motion` |
| Aggregate `all six completed` message | Could | Supply a session-level endpoint | No certificate/competency claim |

### Explicit exclusions

The current prototype should not add:

- weighted points or an arbitrary total score;
- leaderboards, competitive rankings or social comparison;
- lives/health bars or punitive failure loops;
- countdown timers or speed bonuses, because involved coding tasks are intentionally deliberative;
- daily streaks, accounts or retention mechanics;
- randomized option **membership**, loot/rewards or any mechanic that can change answer truth;
- badges named `expert`, `mastered`, `competent`, or similar claims that would imply an unmeasured learner attribute.

These exclusions are intentional design decisions, not claims that such mechanics are universally inappropriate.

## 5. Information architecture and visual direction

The intended screen set is small:

1. **Home / orientation**: title, concise purpose, three-step workflow, feedback legend, educational-use notice, entry to patient roster.
2. **Patient roster**: six visually distinct cards, question counts, neutral complexity, session completion state.
3. **Patient dossier / playthrough start**: identity/demographics, general state, history-availability/source-aware context, `Begin`/`Continue` action.
4. **Question**: progress, task, answer cards/radio controls, dossier access, submit.
5. **Question feedback**: same task context with locked response and elaborated feedback panel.
6. **Patient review**: category counts, per-question review, replay/next-patient actions.

The visual treatment should be contemporary and restrained: generous spacing, clear typography, card/surface hierarchy, a consistent icon set, and a warm clinical-learning rather than hospital-information-system aesthetic. Patient backgrounds should be represented neutrally; demographic diversity is case context, not a basis for stereotyped photography or decorative ethnic cues. Initials or abstract neutral avatars are preferable to photorealistic patient images.

Suggested semantic colour roles are a dark neutral/navy structural colour, a teal/blue interactive accent, and distinct green/amber/red result colours. Exact colours are implementation choices subject to contrast checks. Classification must always include visible text and/or iconography.

Animations should be short and functional: panel transition, progress update, optional patient-completion acknowledgement. They must not delay access to feedback or navigation.

## 6. Playthrough state and architecture boundary

No new MySQL tables are required for `UXBASE-0.1`. The server continues to own authoritative patient/question data and deterministic evaluation. React owns transient presentation state, conceptually:

```text
patient_id
ordered_question_ids[]          # randomized permutation for this playthrough
current_index
responses[question_id]
evaluation_results[question_id]
completed
```

An evaluator result stored in this transient map is immutable within the active playthrough. Review reads it; it does not call a different scoring function. `Play again` creates a fresh state over the same frozen question membership.

Browser `sessionStorage` may be considered later only as reload resilience, keyed to the active baseline identifiers. It is not required by `UXBASE-0.1` and must not be described as longitudinal learner-history persistence.

The feedback/completion layer may aggregate **counts** of existing evaluator classes for presentation. It may not derive a new domain classification, weighted score or rule result.

## 7. Cross-question leakage safeguard

Immediate feedback is permissible only because questions are designed to be independently answerable in any order. A preceding result must never provide information required to solve a later task.

As a concrete pre-freeze authoring check, no accepted code for one current learner question should appear as a displayed answer option for a different question belonging to the same patient unless that overlap is intentionally justified. The current materialized 25-question bank has been checked against this condition and contains no such accepted-code/displayed-option overlap. This check addresses direct code-answer leakage; prose explanations should also be reviewed before freeze for accidental disclosure of a later task's answer.

## 8. Verification obligations introduced by the concept

The implementation test plan should add or update checks for:

- immediate rendering of class/criterion/explanation after submission;
- response locking after successful evaluation;
- `suboptimal` improvement/reference display;
- `none_of_above` feedback in both correct and incorrect cases;
- correct 3/5/6-question progress and completion counts;
- patient-summary reopening without loss of active state;
- read-only final review matching the original evaluator responses;
- replay producing a valid permutation without changing membership or semantics;
- no learner navigation to verification-only questions;
- keyboard operation and visible focus for primary learner controls;
- classification intelligibility without colour alone;
- reduced-motion handling for nonessential animation;
- responsive smoke paths at representative desktop and mobile widths; and
- regression proof that removing presentation/gameful decoration does not change evaluator outputs.

These are software/interaction conformance checks. They do not demonstrate that the interface is usable, enjoyable, motivating or educationally effective for a target population.

## 9. Chapter 3 reporting rule

Chapter 3 should present immediate feedback and the patient-completion review as part of the implemented artefact interaction model. If the stretch goal is implemented before freeze, the UX/UI refinement should be reported briefly in Section 3.1.4 as a **formative implementation iteration**: the core workflow was first made technically functional, inspection showed that it remained too bare to communicate the intended case/feedback model effectively, and a bounded presentation iteration introduced orientation, patient-centred navigation, progress/completion and restrained gameful elements without altering evaluator semantics.

The prose should emphasize traceable design decisions and representative screenshots rather than aesthetic commentary. A compact before/after comparison may be useful if it demonstrates a genuine workflow change. The thesis must continue to state that no usability, engagement or learning-effect study was conducted.

## 10. Implementation order

The stretch goal does not reorder the core dependencies:

1. integrate and prove `MODELBASE-0.2` persistence against MySQL;
2. migrate the PHP repository/evaluator and API to `RULEBASE-0.2` / patient-question semantics;
3. establish the functionally complete React patient/question workflow with immediate feedback and completion review;
4. perform the `UXBASE-0.1` visual/gameful refinement without changing semantic contracts;
5. execute updated UI/integration/E2E regression checks;
6. reconcile/finalize the oracle and historical fixtures, freeze software/data/test identities, and execute principal verification.

If schedule pressure returns, step 4 may be reduced to the `Must` mechanics and accessibility-critical presentation requirements. Steps 1--3 and the verification boundary must not be sacrificed to preserve cosmetic polish.
