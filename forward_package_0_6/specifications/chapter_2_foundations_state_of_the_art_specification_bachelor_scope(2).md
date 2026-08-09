---
title: "Chapter 2 Specification: Foundations - Bachelor Scope"
author: "Juno Marx - Bachelor Thesis Working Specification"
date: "August 2026"
lang: en-GB
papersize: a4
geometry: "margin=20mm"
fontsize: 10pt
linestretch: 1.08
toc: true
toc-depth: 3
numbersections: false
colorlinks: true
linkcolor: black
urlcolor: blue
header-includes:
  - |
    \usepackage{fancyhdr}
    \pagestyle{fancy}
    \fancyhf{}
    \fancyhead[L]{Bachelor Thesis Chapter Specification}
    \fancyhead[R]{Juno Marx}
    \fancyfoot[C]{\thepage}
    \setlength{\headheight}{14pt}
  - |
    \usepackage{enumitem}
    \setlist{leftmargin=*,itemsep=0.20em,topsep=0.35em}
  - |
    \usepackage{microtype}
  - |
    \usepackage{booktabs,longtable,array}
    \renewcommand{\arraystretch}{1.12}
---

**Recommended final-thesis page budget:** approximately 9-12 pages. This is a planning range, not a formal limit. It reflects the advisor's recommendation to keep the complete bachelor's thesis around a proportionate 50-page scale and to compress hierarchy and repetition rather than expand background material.

Chapter 2 supplies the minimum coherent knowledge base required to understand and judge the artefact, its development procedure, and the later technical verification. Following the advisor discussion, the chapter is organised into three broad foundation domains rather than seven parallel sections: **classification and coding**, **digital learning and feedback**, and **software engineering/development**. The chapter should not become a general ICD, education, software-engineering, or design-science textbook.

The chapter should normally end with a short unnumbered synthesis/transition into Chapter 3. A separately numbered "Chapter Summary" section is no longer recommended.

## Controlling research question and internal analytical prompts

**Main research question - current working formulation, pending explicit advisor confirmation**

> How can a web-based learning prototype for the Austrian ICD-10 catalogue be designed, implemented, and technically verified to classify selected coding responses as correct, suboptimal, or incorrect through explicit rule-based feedback?

The advisor stated that formal subquestions are not required and may be removed if they create additional work. The former subquestions are therefore retained in this specification only as **internal analytical prompts**. They may guide traceability, literature selection, modelling, and verification without appearing as formal research questions in the thesis.

**Internal analytical prompt A**

> Which selected coding-error patterns identified in the literature and official Austrian guidance can be represented within the defined prototype scope, and how can they be translated into explicit decision criteria for the three feedback classifications?

**Internal analytical prompt B**

> To what extent does the implemented feedback behaviour conform to the predefined classifications and explanations across the fixed reference-case suite and targeted software tests?

Do not rewrite the main research question merely to accommodate the new chapter hierarchy. Its final wording remains an advisor-confirmation item.

## Fixed bachelor-level boundaries and current decision status

### Fixed boundaries

- The artefact uses a bounded and versioned subset of the Austrian ICD-10 catalogue rather than comprehensive catalogue coverage.
- All cases are synthetic. The prototype does not process real patient data.
- The artefact evaluates represented code selections against represented case information and explicit rules. It does not determine a patient's true diagnosis.
- The software is an educational demonstrator, not a medical device, clinical decision-support, reimbursement, or production system.
- The thesis does not establish usability, acceptance, learning effectiveness, knowledge retention, independent clinical correctness, or reduced real-world coding errors.
- The intended design-science contribution is a bounded, traceable model plus its software instantiation. The data/case model, rule/feedback model, reference-case framework, and technical verifiability are more important than the choice of web stack.
- A bounded pre-freeze UX/UI and gameful-presentation iteration is now an accepted stretch goal. It may improve orientation, workflow clarity and presentational quality, but the thesis still does not claim usability, engagement or learning effectiveness because those outcomes are not empirically evaluated.
- No real-patient study, learner study, or broad catalogue coverage should be introduced to make the work appear more substantial.

### Current evaluation and selection status

- The baseline evaluation remains internal technical conformance through predefined reference cases and targeted software tests. The advisor's latest reply supports systematic testing as a design requirement but does not explicitly answer whether this evaluation alone is sufficient; any additional independent domain-expert review therefore remains unresolved.
- The case/code selection principle is now fixed. No minimum number of cases/codes and no mandatory medical domain are prescribed. The final suite is to be selected purposively from predefined coverage criteria: all three feedback classes, multiple included coding-error patterns, straightforward cases, and at least some more difficult or ambiguous coding situations must be represented.
- Case-suite sufficiency is to be justified through this coverage rather than through an arbitrary target count. Chapter 2 may establish candidate error patterns and the conceptual basis for such coverage; Chapter 3 defines the actual criteria, cases, and frozen subset.
- Presentation is also fixed in principle: representative cases may be explained in the main text, while the complete reference-case set is documented tabularly in an appendix together with the applicable Austrian catalogue version.
- The exact division between verification methodology and reported test results remains to be confirmed. The working assumption is that Chapter 3 defines the procedure and the Results chapter reports the observed outcomes.

## Citation and evidence rule

- Maintain a two-layer evidence policy. Working source audits, requirement provenance, and methodological control records retain the exact printed page or page range for every source-dependent claim where stable printed pagination exists.
- In the thesis-facing text, follow the supervisor-requested HCW citation presentation and use the numbered reference without a page locator. Remove pinpoint locators only at the presentation layer, after the underlying claim has been verified; do not delete them from the working evidence records.
- For non-paginated machine-readable sources such as DIAGLIST, retain native locators such as dataset, worksheet, record/code, and field in the methodological record rather than inventing page numbers.
- Chapter 2 is the literature-intensive chapter. Chapter 3 should normally refer back to these foundations rather than reintroduce the same literature.
- Use primary or authoritative official Austrian sources for catalogue, reporting, LKF, and regulatory-context claims wherever possible.

## How to use this specification

The headings under **Recommended visible thesis structure** are the proposed numbered headings for the thesis. The bullets under **Internal content checkpoints** are drafting and review prompts, not additional subsection titles. Handle them as paragraphs, figures, tables, or short internal signposts.

The specification is intentionally more detailed than the visible thesis hierarchy. Do not create a new numbered subsection merely because a checkpoint needs a paragraph.

## Bachelor-level proportionality rules

- Prefer synthesis over exhaustive review. Include a concept only when it supports a later requirement, model, design choice, verification method, result interpretation, or limitation.
- Keep the visible hierarchy to chapter, section, and subsection level. Do not introduce a fourth numbered level.
- Avoid two-sentence headings and unnecessary terminal punctuation in headings.
- A subsection should support a distinct argumentative task. If a topic can be handled coherently in one paragraph, keep it within its parent subsection.
- Use representative evidence rather than surveying every ICD coding tool, educational approach, software-development model, testing technique, or design-science framework.
- Describe the Austrian/LKF setting only to the depth needed to establish the coding context and artefact boundary. Do not turn Chapter 2 into a financing-system description.
- Preserve the traceability chain, coding-error distinctions, feedback principles, rule logic, testing concepts, and DSR position before peripheral technical detail.
- Avoid repeating the Chapter 1 problem context or research gap. Chapter 2 should deepen concepts, not re-motivate the thesis.

## Recommended visible thesis structure

```text
2 Foundations
  2.1 Classification and Coding Foundations
    2.1.1 Diagnostic Classification, ICD-10, and the Coding Process
    2.1.2 Austrian ICD-10 and LKF Context
    2.1.3 Coding Quality and Coding-Error Patterns
  2.2 Digital Learning and Feedback
    2.2.1 Case-Based Learning and Formative Feedback
    2.2.2 Existing ICD Coding Learning Approaches
    2.2.3 Design Implications for the Artefact
  2.3 Software-Engineering Foundations
    2.3.1 Requirements Engineering and Traceability
    2.3.2 Prototypical and Rule-Based Development
    2.3.3 Software Verification and Testing
    2.3.4 Design Science and Artefact Evaluation
```

After Section 2.3, use one short **unnumbered synthesis/transition** that identifies what is carried into Chapter 3. Do not retain the former numbered `2.8 Chapter Summary` unless an institutional template unexpectedly requires it.

## Structural migration from the previous specification

This mapping is for revision control only and should not appear in the thesis.

| Previous location | Revised destination | Revision logic |
|---|---|---|
| 2.1 Diagnostic Classification and ICD-10 | 2.1.1 Diagnostic Classification, ICD-10, and the Coding Process | Terminology, ICD structure, coding process, and diagnostic-vs-coding accuracy are compressed into one conceptual subsection. |
| 2.2 Austrian ICD-10 and LKF Context | 2.1.2 Austrian ICD-10 and LKF Context | Austrian catalogue, hospital/LKF context, and extramural delimitation remain together but become part of the broader classification/coding foundation. |
| 2.3 Coding Quality and Coding-Error Patterns | 2.1.3 Coding Quality and Coding-Error Patterns | Quality dimensions, error sources, and the correct/suboptimal/incorrect distinction remain together. |
| 2.4 Digital Learning and Feedback | 2.2.1 and 2.2.3 | Case-based and feedback theory is consolidated; explicit artefact implications remain a separate synthesis subsection. |
| 2.5 Existing ICD Coding Learning Approaches | 2.2.2 Existing ICD Coding Learning Approaches | CODIFICO and limited adjacent approaches become part of the digital-learning domain rather than a peer of it. |
| 2.6 Software-Engineering Foundations | 2.3.1 and 2.3.2 | Requirements/traceability and prototypical/rule-based concepts remain as foundations for the concrete Chapter 3 process. |
| 2.7 Verification and Artefact Evaluation | 2.3.3 and 2.3.4 | Software verification/testing and DSR evaluation become subordinate to the software-engineering/development foundation. |
| 2.8 Chapter Summary | Unnumbered closing synthesis | Preserve its bridging function without another numbered section. |

## 2.1 Classification and Coding Foundations

**Section mission**

Establish the conceptual and Austrian coding context needed to discuss coding appropriateness without confusing diagnosis, classification, code assignment, coding quality, and reimbursement context.

**What this section must achieve**

The reader must understand the limited ICD-10 structure used by the prototype, the documented-information-to-code boundary, the authoritative Austrian catalogue context, the relationship to LKF, and why coding appropriateness requires more than syntactic code validity.

**Internal content checkpoints**

- Establish terminology once and reuse it consistently.
- Locate the prototype after clinical assessment/documentation: the artefact receives synthetic documented information and does not reconstruct diagnosis formation.
- Establish the Austrian catalogue/version and sectoral context before discussing quality criteria.
- Distinguish ICD diagnosis coding from LKF grouping/financing and from extramural reporting.
- Narrow general coding-quality literature to error situations that can be represented through explicit synthetic-case and catalogue/rule conditions.

**Core exhibits**

- One compact terminology/process figure or table.
- One concise Austrian source/context table.
- One candidate error-pattern/operationalisability table if space allows.

**Non-goals**

- No history of nosology or ICD revisions.
- No exhaustive description of LKF calculation or Austrian healthcare financing.
- No Austrian prevalence claim unless supported by Austrian data.
- No final project-specific rule catalogue or database schema.

**Completion criteria**

- A reader can distinguish diagnostic correctness from coding correctness.
- A reader can explain why a valid ICD identifier may still be a suboptimal or incorrect response.
- The Austrian normative baseline and included/excluded contexts are clear.
- The later three-class feedback model has a defensible conceptual basis.

### 2.1.1 Diagnostic Classification, ICD-10, and the Coding Process

**Purpose**

Combine the essential terminology, ICD structure, coding-process boundary, and diagnostic-versus-coding distinction into one compact foundation.

**Required content and argument**

- Distinguish medical condition, documented diagnosis, classification class, code, catalogue entry, and coding response.
- Explain only the hierarchy, category/subdivision, specificity, residual/unspecified category, code status, and version properties later used by the artefact.
- Present coding as a transformation from clinical information and documentation to interpretation and code assignment rather than as direct identifier lookup.
- State that downstream coding is constrained by documented information and that the artefact must not invent missing clinical information.
- Explain that syntactic validity, catalogue availability, specificity, and contextual appropriateness are separate properties.
- Distinguish diagnostic accuracy from coding accuracy: a code can accurately reproduce inadequate documentation, while a plausible diagnosis can still be represented by an unsuitable code.
- State the exact intervention boundary: the prototype evaluates represented code selections against synthetic case information and explicit rules, not the patient's true diagnosis.

**Evidence or artefacts to prepare**

- Official ICD or Austrian catalogue documentation for structural claims.
- O'Malley or equivalent coding-process/accuracy literature with printed-page locators.
- One compact process figure with the prototype boundary marked.
- At most one or two examples, preferably reusing an example already needed elsewhere rather than creating parallel examples.

**Keep within scope**

Do not enumerate every coding convention, error source, or catalogue field. Detailed error patterns belong in 2.1.3; project-specific data structures belong in Chapter 3.

**Done when**

The subsection provides all terminology needed by the remainder of the thesis without requiring three separate conceptual subsections.

### 2.1.2 Austrian ICD-10 and LKF Context

**Purpose**

Define the jurisdictional, catalogue, and documentation environment that bounds the artefact, while keeping LKF conceptually separate from diagnostic classification.

**Required content and argument**

- Identify the exact ministry-issued Austrian ICD-10 catalogue version and relevant official coding/documentation guidance used by the project.
- Distinguish normative catalogue data from explanatory, alphabetical, or technical support material where this matters for traceability.
- Explain why version metadata and code status are necessary for reproducibility.
- At hospital level, distinguish diagnosis data, service data, administrative characteristics, and their roles within LKF processes.
- Clarify that ICD codes describe diagnoses and do not themselves carry a fixed monetary tariff.
- Distinguish inpatient and hospital-outpatient documentation only to the extent required by the official sources and prototype context.
- Summarise extramural diagnosis reporting, including the nationwide reporting change effective from 1 July 2026 where relevant, only far enough to delimit that context from hospital LKF and the prototype.
- End with a concise statement of which Austrian source/version and which setting-specific rules the artefact does and does not represent.

**Evidence or artefacts to prepare**

- Official Austrian catalogue and medical-documentation publications.
- Official LKF system description with printed-page locators for precise system claims.
- Official extramural reporting material for the 2026 delimitation.
- Compact source table: authority, title/version, role in the thesis, later destination in Chapter 3.

**Keep within scope**

Do not provide a detailed history of LKF, point calculation, settlement mechanics, ambulatory financing, or legal development. The purpose is coding context and artefact delimitation.

**Done when**

The reader can identify the exact Austrian normative baseline, distinguish ICD coding from LKF financing logic, and understand why extramural reporting is related but institutionally distinct.

### 2.1.3 Coding Quality and Coding-Error Patterns

**Purpose**

Build the conceptual basis for selecting a small, operationalisable set of coding-response situations and for distinguishing correct, suboptimal, and incorrect outcomes.

**Required content and argument**

- Introduce only quality dimensions relevant to the artefact, such as accuracy, completeness, specificity, consistency, plausibility, and rule conformance.
- Explain why reported coding-accuracy figures depend on reference standard, granularity, coding system, use context, and definition of agreement.
- Group sources of inaccuracy across documentation, interpretation, and coding-rule application, then narrow to situations representable at code-selection/rule-application level.
- Distinguish error from acceptable disagreement or alternative coding where the literature/guidance permits alternatives.
- Discuss insufficient specificity, contextual mismatch, invalid/inactive codes, unsupported precision, ambiguity, and acceptable alternatives only as **candidate** patterns.
- Explain why a binary correct/incorrect model is insufficient for the selected learning problem and why explicit conditions and precedence are required for the three feedback classes.
- Establish the conceptual possibility of a catalogue-valid response being less appropriate than another response when an explicit criterion such as supported specificity distinguishes them. Do not equate catalogue validity with optimal coding.
- Make clear that **suboptimal is not a residual category for uncertainty or disagreement**. A middle classification is defensible only where represented case facts and a source-backed criterion make the preference objectively decidable; otherwise an alternative must be treated as acceptable or the situation excluded from deterministic classification.
- Treat apparently "wrong hierarchy level" or "formally permissible but less suitable" responses as candidate situations rather than automatically assigning them to `suboptimal`; their eventual class depends on the applicable Austrian criterion and the rule model.
- Reserve the final included pattern taxonomy and operational definitions for Chapter 3.

**Evidence or artefacts to prepare**

- Coding-quality/error literature such as O'Malley, Campbell, Stausberg, and other sources already verified for the thesis.
- Official Austrian guidance where it supports a candidate rule or distinction.
- Candidate-pattern table: pattern, evidence basis, observable condition, representability, reason for inclusion/exclusion.

**Keep within scope**

Do not claim that coder disagreement proves one answer wrong, that the selected literature establishes Austrian error prevalence, or that every coding situation can be objectively classified into three outcomes.

**Done when**

Every candidate pattern has a possible observable criterion, unsupported/clinically dependent patterns are excluded, and the reader understands why Chapter 3 must formalise the final taxonomy.

## 2.2 Digital Learning and Feedback

**Section mission**

Provide the educational rationale for synthetic case-based interaction and explanatory feedback, then position the artefact against a small number of relevant ICD-coding learning approaches.

**What this section must achieve**

The reader must understand why cases are used, why feedback should communicate more than a binary verdict, what directly relevant prior artefacts demonstrate, and which source-backed design principles are carried into requirements.

**Internal content checkpoints**

- Keep educational theory tied to features the prototype can implement.
- Distinguish case-based controlled interaction from evidence of learning effectiveness.
- Distinguish verification feedback from elaboration and corrective direction.
- Establish that feedback follows a learner action and may be shown immediately; the later project-specific decision to lock the response, show elaboration before the next question and provide a patient-level completion review belongs in Chapter 3.
- Use CODIFICO as the principal comparator; include adjacent approaches only when they add a distinct lesson.
- If gameful presentation is discussed, keep it tightly tied to the implemented stretch goal and to the warning that game mechanics must not displace the coding/feedback objective.
- End by translating the literature into a short set of technically inspectable design implications.

**Non-goals**

- No broad history of e-learning, gamification, virtual patients, or instructional design. A short gamification passage is permissible only because a bounded gameful UX iteration is now a concrete downstream design decision.
- No claim that feedback theory proves this prototype improves learning.
- No exhaustive market/tool survey or unsupported statement that no other tools exist.

### 2.2.1 Case-Based Learning and Formative Feedback

**Purpose**

Justify synthetic case-based interaction and feedback that communicates classification plus the criterion behind it.

**Required content and argument**

- Explain case-based interaction at a high level and why synthetic cases permit deliberate control of documentation and selected coding-error situations.
- State that realism, transfer, usability, and learning effects are not measured.
- Define verification and elaboration in the limited sense needed by the artefact.
- Focus on task- and process-related, response-specific feedback rather than praise or learner-level judgement.
- Explain why exact-answer matching alone is insufficient for a model that includes suboptimal responses and acceptable alternatives.
- Note the need for concise feedback that identifies the criterion and, where appropriate, corrective direction without unnecessary overload.
- Make the temporal relation explicit at the level supported by the feedback literature: feedback is responsive to a learner action. Do not turn Chapter 2 into an interface state-machine description.

**Evidence or artefacts to prepare**

- Shute and Hattie and Timperley with printed-page locators for precise feedback claims.
- One suitable case-based/virtual-patient learning source.
- One example showing classification, criterion, and explanation.

**Done when**

The educational rationale leads directly to implementable feedback properties without making an effectiveness claim.

### 2.2.2 Existing ICD Coding Learning Approaches

**Purpose**

Position the artefact against CODIFICO and only those adjacent approaches needed to establish the relevant state of the art.

**Required content and argument**

- Summarise CODIFICO's target population, interaction/game mechanics, evaluation design, acceptance findings, and diagnosis-versus-coding findings accurately.
- Use CODIFICO's immediate cue/retry/time-pressure mechanics as comparison points rather than as features that must be copied. In particular, do not infer that timers, points or competitive mechanics improve coding learning.
- Distinguish acceptance, diagnosis determination, and diagnosis-coding outcomes.
- If the later UX/gamification stretch goal needs one broader evidence boundary, Gentry et al.'s health-professions serious-gaming/gamification review may be used to establish the limited/heterogeneous evidence and the risk that game mechanics distract from the educational objective. Do not expand this into a second literature review.
- Mention the WHO tool or other adjacent approaches only if they provide a distinct comparison point. Do not add another artefact merely to satisfy a numerical count.
- Compare intended objective, feedback, evaluation evidence, relevance, and limitation rather than implementation trivia.
- Avoid restating the Chapter 1 research gap or claiming superiority before the present artefact is evaluated.

**Evidence or artefacts to prepare**

- Primary CODIFICO publication with printed-page citations for empirical claims.
- `Gentry2019SeriousGaming` only if the narrow gameful-design boundary is discussed; use it cautiously and do not convert low-quality/heterogeneous evidence into an effectiveness claim for this artefact.
- Optional compact comparison table with one row per genuinely useful approach.

**Done when**

Prior work appears once, accurately and proportionately, and provides specific lessons rather than serving as a straw man.

### 2.2.3 Design Implications for the Artefact

**Purpose**

Synthesize educational-feedback literature and relevant prior artefacts into a small number of bounded inputs for Chapter 3 requirements derivation.

**Required content and argument**

- Require an explicit classification output.
- Require identification of the criterion/rule responsible for that outcome.
- Require a concise explanation and corrective direction where supported. In particular, a `suboptimal` response must communicate the source-backed improvement criterion rather than merely display the middle-category label.
- Carry forward immediate, response-specific feedback as the default interaction implication. Chapter 3 may operationalise this as a locked post-submission feedback state before the next question.
- Permit a patient-level completion/review view as a project-specific synthesis of already-returned feedback. Avoid treating `correct`, `suboptimal`, and `incorrect` as an unvalidated interval scale or deriving arbitrary weighted points from them.
- Require traceability from explanation to rule and source basis.
- Preserve intended-use boundaries by avoiding diagnostic claims.
- Distinguish properties that can be technically verified from outcomes that would require learner or expert studies.
- State that the intended coding objective must remain aligned across requirements, implementation, reference cases, and verification.
- Allow restrained gameful mechanics such as visible progress, case completion and replay/randomisation where they do not alter question membership, rule inputs or classification truth. Keep engagement/usability/effect claims out of scope.

**Evidence or artefacts to prepare**

- Short design-principle table: principle, source, artefact implication, later requirement category.

**Keep within scope**

Do not present the final requirement set here. This subsection supplies design inputs; Chapter 3 records the actual derivation and selected requirements.

**Done when**

The educational/state-of-the-art material has a concrete destination in Chapter 3 and no principle is included merely because it is pedagogically interesting.

## 2.3 Software-Engineering Foundations

**Section mission**

Supply the software-engineering, testing, and DSR concepts needed to understand the concrete prototype-development and verification procedure in Chapter 3.

**What this section must achieve**

The reader must understand how evidence becomes traceable requirements, how a bounded prototype and explicit rules can be developed, how conformance testing works, and why construction plus controlled evaluation constitute the thesis's DSR-oriented research strategy.

**Internal content checkpoints**

- Introduce only concepts that are actually used in Chapter 3.
- Establish a source-to-requirement-to-model/rule-to-implementation-to-test traceability chain.
- Treat prototyping as a bounded development approach rather than surveying lifecycle models.
- Treat deterministic rule logic as an inspectable design choice, not a novel algorithm.
- Distinguish verification of conformance from external/domain validation.
- Keep DSR theory here; Chapter 3 should apply it rather than repeat it.

**Non-goals**

- No Scrum/DevOps/waterfall/V-model catalogue.
- No formal V-model or Lastenheft requirement arising merely from the exploratory advisor discussion.
- No expert-systems survey.
- No full DSR framework comparison.
- No production-quality software-testing textbook.

### 2.3.1 Requirements Engineering and Traceability

**Purpose**

Explain how explicit, testable requirements can be derived from documents, literature, intended use, and project constraints, and how their provenance and verification links can be preserved.

**Required content and argument**

- Define functional/relevant non-functional requirements and acceptance criteria at a practical level.
- Explain document- and literature-based derivation because no stakeholder elicitation study is planned.
- Introduce requirement identifiers and forward/backward traceability from source to requirement and from requirement to model, implementation, and verification evidence.
- Distinguish source-backed domain/design requirements from pragmatic implementation constraints.
- Explain that supervisory/project constraints may legitimately shape scope, architecture and presentation requirements but do not become independent domain evidence or clinical validation by doing so.
- Establish the requirement qualities needed later for the concrete prototype: separability of reference data, evaluation logic and presentation responsibilities; explicit version control; reproducible rule behaviour; explainable feedback; and systematic testability. Leave the final project-specific `REQ-*` records to Chapter 3.
- State that document-based derivation is not equivalent to stakeholder or expert validation.

**Evidence or artefacts to prepare**

- One authoritative requirements source or standard-level overview.
- Conceptual traceability diagram.
- Generic requirement-record fields without presenting the final project-specific set.

**Done when**

The reader can understand and later audit the Chapter 3 requirements procedure without needing a separate requirements-engineering literature review there.

### 2.3.2 Prototypical and Rule-Based Development

**Purpose**

Provide the conceptual basis for bounded prototype construction, controlled revision, and explicit deterministic rule execution.

**Required content and argument**

- Explain iterative/incremental refinement only to the degree needed to describe how requirements, models, implementation, and formative tests may lead to documented revisions.
- Emphasise that the final thesis reports the actual development path and frozen baseline rather than claiming adherence to a heavyweight lifecycle model.
- Define rule condition, required inputs, outcome, precedence, conflict handling, and explanation payload.
- Explain why explicit rules support inspection, traceability, repeatable execution, and targeted testing.
- Acknowledge rule incompleteness, maintenance burden, limited handling of unforeseen cases, and dependency on the quality of predefined criteria.

**Evidence or artefacts to prepare**

- One suitable software-development/prototyping source if a specific development claim requires support.
- One generic rule representation example.
- Optional conceptual loop showing requirements/model/implementation/formative-test revision.

**Keep within scope**

Do not select the V-model simply because it was discussed during the call, and do not present a formal Lastenheft/SPEC unless the project independently uses one. Do not present the final rule catalogue or precedence order here.

**Done when**

Chapter 3 can describe the actual prototype process and rule model without introducing new software-engineering theory.

### 2.3.3 Software Verification and Testing

**Purpose**

Define the testing concepts required for the later technical conformance examination.

**Required content and argument**

- Define test case, input/precondition, expected result, actual result, pass/fail, regression baseline, and traceability to a requirement or rule.
- Introduce unit, integration, end-to-end, negative, boundary, and regression testing as a compact set grouped by responsibility rather than separate surveys.
- Explain predefined expectations and why the final implementation output must not be used to create its own expected result.
- Explain the purpose of negative and boundary cases for rule-based behaviour.
- Explain coverage as a relation between the declared behaviours/rules and the cases/tests that exercise them. For the bounded artefact, adequacy is established by justified coverage of the selected feedback classes, error patterns and relevant rule boundaries, not by an arbitrary number of test cases.
- State clearly that passing tests demonstrates conformance to predefined expectations, not the independent clinical truth of those expectations.

**Evidence or artefacts to prepare**

- One authoritative testing source or standard-level overview.
- Concise taxonomy/mapping table if useful.

**Keep within scope**

Do not expand into performance, penetration, scalability, production acceptance, or exhaustive QA unless a specific implemented requirement makes one of these necessary.

**Done when**

The terminology needed for Chapter 3's verification procedure and the Results chapter is fixed and the limits of technical verification are explicit.

### 2.3.4 Design Science and Artefact Evaluation

**Purpose**

Provide the general research-method foundation that the advisor requested be kept outside Chapter 3's practical description.

**Required content and argument**

- Define DSR only to the extent necessary to explain problem-oriented artefact construction and evaluation.
- Identify the data/case model, error/feedback model, rule/reference-case structures as model-level outputs and the web prototype as an instantiation.
- Explain build-and-evaluate logic and distinguish formative correction during development from the final technical examination of a frozen baseline.
- Explain why controlled reference cases and targeted tests are appropriate evidence for the thesis's limited technical-conformance claim.
- State that DSR does not itself prove usefulness, learning effectiveness, clinical validity, or generalisability.
- Introduce the chosen DSR process/framework here, with the specific Chapter 3 activities mapped to it later rather than re-explaining the framework in Chapter 3.

**Evidence or artefacts to prepare**

- Hevner, Wieringa, Peffers/FEDS or the actually selected primary DSR sources, using only the minimum set required for the chosen framing.
- Generic artefact-and-evidence mapping if it saves explanatory prose.

**Keep within scope**

Do not compare numerous DSR schools or imply that every implementation decision is a research contribution.

**Done when**

Chapter 3 can begin with a short cross-reference to the selected DSR foundation and proceed directly to what was actually done.

## Unnumbered closing synthesis and transition

The chapter should end with a short synthesis rather than a numbered summary section. It should carry forward only the knowledge required by Chapter 3:

- the Austrian catalogue/version and setting boundary;
- the candidate coding-quality/error distinctions that can be operationalised;
- the principle that `suboptimal` requires an explicit, objectively decidable improvement criterion rather than an intuitive middle judgement;
- the feedback/design principles;
- requirements and traceability concepts;
- explicit rule-logic concepts;
- software-verification and coverage concepts; and
- the DSR build/evaluate position.

State that the **final** requirement set, prototype subset, operational feedback taxonomy, rule catalogue, reference cases, architecture/implementation choices, and verification procedure are project-specific decisions documented in Chapter 3.

Do not introduce new citations, repeat the Chapter 1 research gap, or preview Chapter 3 subsection by subsection.

## Chapter 2 exit checklist

- [ ] The visible chapter has three major foundation sections rather than seven equal-level topics.
- [ ] The chapter title is **Foundations** unless the advisor subsequently requests another title.
- [ ] The chapter can be read without knowledge of implementation code.
- [ ] Every substantial literature discussion produces a design or method implication used later.
- [ ] Precise empirical, official, or framework claims have been verified against retained printed-page locators in the working evidence record, while thesis-facing citations follow the supervisor-requested HCW presentation convention.
- [ ] The Austrian setting is presented as the normative context, not the sole novelty claim.
- [ ] LKF is clearly distinguished from diagnostic classification and is discussed only to the depth necessary for the coding context.
- [ ] Extramural reporting is used for delimitation rather than becoming a second financing-system review.
- [ ] CODIFICO and adjacent tools are represented proportionately; no arbitrary requirement for multiple comparable games is introduced.
- [ ] `Suboptimal` is conceptually distinguished from both incorrect coding and unresolved/acceptable alternatives, without prematurely defining the project-specific rule set.
- [ ] The final implemented taxonomy, requirements, subset, rules, and test results have not been prematurely reported.
- [ ] General DSR theory is complete enough that Chapter 3 need only cross-reference and apply it.
- [ ] The former formal subquestions are used only as internal prompts unless the advisor explicitly asks to retain them.
- [ ] The closing synthesis is unnumbered and concise.
- [ ] The chapter fits the revised page budget or has a documented reason to exceed it.
