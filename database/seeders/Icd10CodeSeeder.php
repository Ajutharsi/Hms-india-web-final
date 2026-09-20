<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Icd10Code;

class Icd10CodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['code' => 'A00', 'description' => 'Cholera', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'A00-A09 Intestinal infectious diseases'],
            ['code' => 'A01.0', 'description' => 'Typhoid fever', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'A00-A09 Intestinal infectious diseases'],
            ['code' => 'A09', 'description' => 'Infectious gastroenteritis and colitis, unspecified', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'A00-A09 Intestinal infectious diseases'],
            ['code' => 'A15', 'description' => 'Respiratory tuberculosis', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'A15-A19 Tuberculosis'],
            ['code' => 'A90', 'description' => 'Dengue fever', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'A90-A99 Arthropod-borne viral fevers'],
            ['code' => 'B15', 'description' => 'Acute hepatitis A', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'B15-B19 Viral hepatitis'],
            ['code' => 'B24', 'description' => 'Unspecified human immunodeficiency virus disease', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'B20-B24 HIV disease'],
            ['code' => 'B50', 'description' => 'Plasmodium falciparum malaria', 'chapter' => 'Certain infectious and parasitic diseases', 'category' => 'B50-B64 Protozoal diseases'],
            ['code' => 'C50', 'description' => 'Malignant neoplasm of breast', 'chapter' => 'Neoplasms', 'category' => 'C50 Malignant neoplasm of breast'],
            ['code' => 'C61', 'description' => 'Malignant neoplasm of prostate', 'chapter' => 'Neoplasms', 'category' => 'C60-C63 Malignant neoplasms of male genital organs'],
            ['code' => 'D50', 'description' => 'Iron deficiency anaemia', 'chapter' => 'Diseases of the blood and blood-forming organs', 'category' => 'D50-D53 Nutritional anaemias'],
            ['code' => 'E03', 'description' => 'Other hypothyroidism', 'chapter' => 'Endocrine, nutritional and metabolic diseases', 'category' => 'E00-E07 Disorders of thyroid gland'],
            ['code' => 'E10', 'description' => 'Type 1 diabetes mellitus', 'chapter' => 'Endocrine, nutritional and metabolic diseases', 'category' => 'E10-E14 Diabetes mellitus'],
            ['code' => 'E11', 'description' => 'Type 2 diabetes mellitus', 'chapter' => 'Endocrine, nutritional and metabolic diseases', 'category' => 'E10-E14 Diabetes mellitus'],
            ['code' => 'E66', 'description' => 'Obesity', 'chapter' => 'Endocrine, nutritional and metabolic diseases', 'category' => 'E65-E68 Obesity and other hyperalimentation'],
            ['code' => 'F32', 'description' => 'Depressive episode', 'chapter' => 'Mental and behavioural disorders', 'category' => 'F30-F39 Mood disorders'],
            ['code' => 'F41', 'description' => 'Other anxiety disorders', 'chapter' => 'Mental and behavioural disorders', 'category' => 'F40-F48 Neurotic, stress-related disorders'],
            ['code' => 'G40', 'description' => 'Epilepsy', 'chapter' => 'Diseases of the nervous system', 'category' => 'G40-G47 Episodic and paroxysmal disorders'],
            ['code' => 'G43', 'description' => 'Migraine', 'chapter' => 'Diseases of the nervous system', 'category' => 'G40-G47 Episodic and paroxysmal disorders'],
            ['code' => 'H10', 'description' => 'Conjunctivitis', 'chapter' => 'Diseases of the eye and adnexa', 'category' => 'H10-H13 Disorders of conjunctiva'],
            ['code' => 'H66', 'description' => 'Suppurative and unspecified otitis media', 'chapter' => 'Diseases of the ear and mastoid process', 'category' => 'H65-H75 Diseases of middle ear and mastoid'],
            ['code' => 'I10', 'description' => 'Essential (primary) hypertension', 'chapter' => 'Diseases of the circulatory system', 'category' => 'I10-I15 Hypertensive diseases'],
            ['code' => 'I20', 'description' => 'Angina pectoris', 'chapter' => 'Diseases of the circulatory system', 'category' => 'I20-I25 Ischaemic heart diseases'],
            ['code' => 'I21', 'description' => 'Acute myocardial infarction', 'chapter' => 'Diseases of the circulatory system', 'category' => 'I20-I25 Ischaemic heart diseases'],
            ['code' => 'I50', 'description' => 'Heart failure', 'chapter' => 'Diseases of the circulatory system', 'category' => 'I30-I52 Other forms of heart disease'],
            ['code' => 'I63', 'description' => 'Cerebral infarction', 'chapter' => 'Diseases of the circulatory system', 'category' => 'I60-I69 Cerebrovascular diseases'],
            ['code' => 'I83', 'description' => 'Varicose veins of lower extremities', 'chapter' => 'Diseases of the circulatory system', 'category' => 'I80-I89 Diseases of veins/lymphatics'],
            ['code' => 'J00', 'description' => 'Acute nasopharyngitis (common cold)', 'chapter' => 'Diseases of the respiratory system', 'category' => 'J00-J06 Acute upper respiratory infections'],
            ['code' => 'J02', 'description' => 'Acute pharyngitis', 'chapter' => 'Diseases of the respiratory system', 'category' => 'J00-J06 Acute upper respiratory infections'],
            ['code' => 'J18', 'description' => 'Pneumonia, organism unspecified', 'chapter' => 'Diseases of the respiratory system', 'category' => 'J09-J18 Influenza and pneumonia'],
            ['code' => 'J44', 'description' => 'Chronic obstructive pulmonary disease', 'chapter' => 'Diseases of the respiratory system', 'category' => 'J40-J47 Chronic lower respiratory diseases'],
            ['code' => 'J45', 'description' => 'Asthma', 'chapter' => 'Diseases of the respiratory system', 'category' => 'J40-J47 Chronic lower respiratory diseases'],
            ['code' => 'K21', 'description' => 'Gastro-oesophageal reflux disease', 'chapter' => 'Diseases of the digestive system', 'category' => 'K20-K31 Diseases of oesophagus, stomach, duodenum'],
            ['code' => 'K29', 'description' => 'Gastritis and duodenitis', 'chapter' => 'Diseases of the digestive system', 'category' => 'K20-K31 Diseases of oesophagus, stomach, duodenum'],
            ['code' => 'K35', 'description' => 'Acute appendicitis', 'chapter' => 'Diseases of the digestive system', 'category' => 'K35-K38 Diseases of appendix'],
            ['code' => 'K80', 'description' => 'Cholelithiasis (gallstones)', 'chapter' => 'Diseases of the digestive system', 'category' => 'K80-K87 Disorders of gallbladder/biliary tract'],
            ['code' => 'L01', 'description' => 'Impetigo', 'chapter' => 'Diseases of the skin and subcutaneous tissue', 'category' => 'L00-L08 Infections of skin'],
            ['code' => 'L20', 'description' => 'Atopic dermatitis', 'chapter' => 'Diseases of the skin and subcutaneous tissue', 'category' => 'L20-L30 Dermatitis and eczema'],
            ['code' => 'M17', 'description' => 'Osteoarthritis of knee', 'chapter' => 'Diseases of the musculoskeletal system', 'category' => 'M15-M19 Arthrosis'],
            ['code' => 'M54', 'description' => 'Dorsalgia (back pain)', 'chapter' => 'Diseases of the musculoskeletal system', 'category' => 'M50-M54 Other dorsopathies'],
            ['code' => 'M79', 'description' => 'Other soft tissue disorders', 'chapter' => 'Diseases of the musculoskeletal system', 'category' => 'M79 Other soft tissue disorders'],
            ['code' => 'N18', 'description' => 'Chronic kidney disease', 'chapter' => 'Diseases of the genitourinary system', 'category' => 'N17-N19 Renal failure'],
            ['code' => 'N39', 'description' => 'Urinary tract infection, site not specified', 'chapter' => 'Diseases of the genitourinary system', 'category' => 'N30-N39 Other diseases of urinary system'],
            ['code' => 'O80', 'description' => 'Single spontaneous delivery', 'chapter' => 'Pregnancy, childbirth and the puerperium', 'category' => 'O80-O84 Delivery'],
            ['code' => 'P07', 'description' => 'Disorders related to short gestation and low birth weight', 'chapter' => 'Perinatal conditions', 'category' => 'P05-P08 Disorders related to fetal growth'],
            ['code' => 'R05', 'description' => 'Cough', 'chapter' => 'Symptoms, signs and abnormal clinical findings', 'category' => 'R00-R09 Symptoms of circulatory/respiratory systems'],
            ['code' => 'R50', 'description' => 'Fever of unknown origin', 'chapter' => 'Symptoms, signs and abnormal clinical findings', 'category' => 'R50-R69 General symptoms and signs'],
            ['code' => 'R51', 'description' => 'Headache', 'chapter' => 'Symptoms, signs and abnormal clinical findings', 'category' => 'R50-R69 General symptoms and signs'],
            ['code' => 'S06', 'description' => 'Intracranial injury', 'chapter' => 'Injury, poisoning and external causes', 'category' => 'S00-S09 Injuries to the head'],
            ['code' => 'S52', 'description' => 'Fracture of forearm', 'chapter' => 'Injury, poisoning and external causes', 'category' => 'S50-S59 Injuries to elbow and forearm'],
            ['code' => 'T78', 'description' => 'Anaphylactic shock, unspecified', 'chapter' => 'Injury, poisoning and external causes', 'category' => 'T78-T88 Other adverse effects'],
            ['code' => 'Z00', 'description' => 'General medical examination', 'chapter' => 'Factors influencing health status', 'category' => 'Z00-Z13 Persons encountering health services for examination'],
        ];

        foreach ($codes as $code) {
            Icd10Code::updateOrCreate(
                ['code' => $code['code']],
                array_merge($code, ['is_active' => true])
            );
        }
    }
}
