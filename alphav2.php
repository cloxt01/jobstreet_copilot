<?php
$file_auth = 'auth.json';
$file_config = 'config.json';

function calculateKeyOccurrences($text, $keywords) {
    $sum = 0;
    foreach ($keywords as $key) {
        $sum += substr_count($text, $key);
    }
    return $sum;
}
function generateUUIDv4() {
    // Hasilkan 16 byte acak
    $data = random_bytes(16);

    // Set bit ke-4 dan ke-3 dari byte ke-7 dan ke-9 sesuai dengan spesifikasi UUID v4
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // Versi 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // Varian

    // Format byte menjadi string UUID
    return sprintf(
        '%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x',
        ord($data[0]), ord($data[1]), ord($data[2]), ord($data[3]),
        ord($data[4]), ord($data[5]),
        ord($data[6]), ord($data[7]),
        ord($data[8]), ord($data[9]),
        ord($data[10]), ord($data[11]), ord($data[12]), ord($data[13]), ord($data[14]), ord($data[15])
    );
}
function job($url,$cookie,$user_agent){
  $ch = curl_init($url);
  $headers = [
      "Host: www.jobstreet.co.id",
      'sec-ch-ua: "Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"',
      "seek-request-country: ID",
      "sec-ch-ua-mobile: ?1",
      "user-agent: $user_agent",
      "accept: application/json",
      "seek-request-brand: jobstreet",
      "x-seek-site: Chalice",
      "x-seek-checksum: 12bd713d",
      'sec-ch-ua-platform: "Android"',
      "sec-fetch-site: same-origin",
      "sec-fetch-mode: cors",
      "sec-fetch-dest: empty",
      "referer: https://www.jobstreet.co.id/id/jobs-in-information-communication-technology?subclassification=6288%2C6290%2C6291%2C6289%2C6293%2C6303",
      "accept-encoding: gzip, deflate, br, zstd",
      "accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7",
      "cookie: $cookie"
      
  ];
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
  
  // Eksekusi cURL dan ambil responsnya
  $response = curl_exec($ch);
  curl_close($ch);
  $job_decode = json_decode($response, true);
  $job_content = $job_decode['data'];
  return $job_content;
}
function graphql($url,$data,$auth,$cookie,$user_agent) {
    $ch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Content-Length: '.strlen($data),
        'Authorization: '.$auth,
        'User-Agent: '.$user_agent,
        'Accept: application/features.seek.all+json, */*',
        'Origin: https://www.jobstreet.co.id',
        'Cookie: '.$cookie,
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // Eksekusi curl dan dapatkan respons
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$httpCode,$response]; // Mengembalikan respons
}
function authLoad($url,$file,$user_agent,$session_id,$client_id){
  if(file_exists($file)){
    $file_content = file_get_contents($file);
    $file_decode = json_decode($file_content,true);
    echo "[file] [$file] file ditemukan\n";
    $is_valid = array_key_exists('access_token',$file_decode) && array_key_exists('refresh_token',$file_decode);
    if($is_valid){
      echo "[token] token ditemukan\n";
      return [$file_decode['access_token'],$file_decode['refresh_token']];
    }
    else {
      echo "[token] token tidak ditemukan\n";
      die();
    }
  }
  else if(!file_exists($file)){
    echo "[file] [$file] file tidak ditemukan/rusak\n";
    die();
  }
  else {
    echo "[file] [$file] sepertinya ada yang salah\n";
    die();
  }
}
function authSave($url,$file,$data,$user_agent){
// Inisialisasi cURL
  $url = 'https://login.seek.com/oauth/token';
  $ch = curl_init();
  $headers = [
      'upgrade-insecure-requests: 1',
      'user-agent: '.$user_agent,
      'accept: application/json',
      'content-type: application/json',
      'content-length: '.strlen($data),
      'referer: https://www.jobstreet.co.id/job',
      'accept-encoding: gzip',
      'accept-language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7'
  ];
  // Set opsi cURL
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_ENCODING, "gzip");
  
  // Eksekusi permintaan
  $response = curl_exec($ch);
  $response_array = json_decode($response,true);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  // Periksa kesalaha
  curl_close($ch);
  if($httpCode == 200 && array_key_exists('access_token',$response_array) && file_put_contents($file,$response)){
    echo "[token] token berhasil di update\n";
  } else if(isset($response_array['error_description'])){
    $error_info = $response_array['error_description'];
    echo "[error] ".$error_info."\n";
  }
  else {
    echo "[token] token gagal di update\n";
  }
}
function authSetup($url,$file,$user_agent,$session_id,$client_id){
  $auth_setup = authLoad($url,$file,$user_agent,$session_id,$client_id);
  $auth = $auth_setup[0];
  $refresh_token = $auth_setup[1];
  $auth_params = json_encode([
          "redirect_uri" => "https://www.jobstreet.co.id/oauth/callback/",
          "initial_scope" => "openid profile email offline_access",
          "JobseekerSessionId" => $session_id,
          "identity_sdk_version" => "6.54.0",
          "refresh_href" => "https://www.jobstreet.co.id/id/job/",
          "client_id" => $client_id,
          "grant_type" => "refresh_token",
          "refresh_token" => $refresh_token
          ],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $auth_update = authSave($url,$file,$auth_params,$user_agent);
  return ("Bearer ".$auth);
}

$config_content = file_get_contents($file_config);
$config = json_decode($config_content,true);
$corelation_id = generateUUIDv4();
$client_id = $config['app']['client_id'];
$session_id = $config['app']['session_id'];
$coverletters_id = $config['app']['coverletters_id'];
$coverletters_text = $config['app']['coverletters_text'];
$resume = $config['app']['resume'];
$user_agent = $config['headers']['user_agent'];
$refreshJobs = $config['task']['refreshJobs'];
$refreshAuth = $config['task']['refreshAuth'];
$cookie = $config['headers']['cookie'];
$pageSize = $config['searchBy']['pageSize'];
$seekSelectAllPages = $config['searchBy']['seekSelectAllPages'];
$subclassification = $config['searchBy']['subclassification'];
$classification = $config['searchBy']['classification'];
$sortmode = $config['searchBy']['sortmode'];

$key1 = ['lama', 'experience', 'year', 'pengalaman', 'tahun', 'Pengalaman'];
$key2 = ['language', 'Bahasa', 'bahasa'];

//$url_job = "https://www.jobstreet.co.id/id/jobs-in-information-communication-technology?sortmode=KeywordRelevance";
$url_job = "https://www.jobstreet.co.id/api/chalice-search/v4/search?siteKey=ID-Main&sourcesystem=houston&userqueryid=98fb54df525fed4ec5d86e111a6cc3d3-2245169&userid=$session_id&usersessionid=$session_id&eventCaptureSessionId=$session_id&page=1&seekSelectAllPages=$seekSelectAllPages&classification=$classification&subclassification=$subclassification&sortmode=$sortmode&pageSize=$pageSize&include=seodata&locale=id-ID";
$url_auth = "https://login.seek.com/oauth/token";
$url_graphql = 'https://www.jobstreet.co.id/graphql';

$auth = authSetup($url_auth,$file_auth,$user_agent,$session_id,$client_id);
$jobData = job($url_job, $cookie, $user_agent);
$last_auth = time();
while(true){
$Curtime=time();
if(($Curtime - $last_auth) >= $refreshAuth){
  $auth = authSetup($url_auth,$file_auth,$user_agent,$session_id,$client_id);
  echo "[token] token di perbarui\n";
}
$jobData = job($url_job, $cookie, $user_agent);
foreach ($jobData as $job){
  $job_id = $job['id'];
  $job_company = $job['advertiser']['description'];
  $job_location = $job['location'];
  $corelation_id = generateUUIDv4();
  $graphql_params = [
    '{"operationName":"jobDetailsPersonalised","variables":{"id":"'.$job_id.'","languageCode":"id","locale":"id-ID","timezone":"Asia/Jakarta","zone":"asia-4"},"query":"query jobDetailsPersonalised($id: ID!, $tracking: JobDetailsTrackingInput, $locale: Locale!, $zone: Zone!, $languageCode: LanguageCodeIso!, $timezone: Timezone!) {\n  jobDetails(id: $id, tracking: $tracking) {\n    personalised {\n      isSaved\n      appliedDateTime {\n        longAbsoluteLabel(locale: $locale, timezone: $timezone)\n        __typename\n      }\n      topApplicantBadge {\n        label(locale: $locale)\n        description(locale: $locale, zone: $zone)\n        __typename\n      }\n      salaryMatch {\n        ... on JobProfileMissingSalaryPreference {\n          label(locale: $locale)\n          __typename\n        }\n        ... on JobProfileSalaryMatch {\n          label(locale: $locale)\n          salaryPreference(locale: $locale, languageCode: $languageCode) {\n            id\n            description\n            country {\n              countryCode\n              name\n              __typename\n            }\n            currencyCode\n            amount\n            salaryType\n            __typename\n          }\n          __typename\n        }\n        ... on JobProfileSalaryNoMatch {\n          label(locale: $locale)\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}','[{"operationName":"GetJobApplicationProcess","variables":{"jobId":"'.$job_id.'","isAuthenticated":true,"locale":"id-ID"},"query":"query GetJobApplicationProcess($jobId: ID!, $isAuthenticated: Boolean!, $locale: Locale) {\n  jobApplicationProcess(jobId: $jobId) {\n    ...LocationFragment\n    ...ClassificationFragment\n    ...DocumentsFragment\n    ...QuestionnaireFragment\n    job {\n      ...JobFragment\n      __typename\n    }\n    linkOut\n    extractedRoleTitles\n    __typename\n  }\n}\n\nfragment LocationFragment on JobApplicationProcess {\n  location {\n    id\n    name\n    __typename\n  }\n  state {\n    id\n    __typename\n  }\n  area {\n    id\n    name\n    __typename\n  }\n  __typename\n}\n\nfragment ClassificationFragment on JobApplicationProcess {\n  classification {\n    id\n    name\n    subClassification {\n      id\n      name\n      __typename\n    }\n    __typename\n  }\n  __typename\n}\n\nfragment DocumentsFragment on JobApplicationProcess {\n  documents {\n    lastAppliedResumeIdPrefill @include(if: $isAuthenticated)\n    selectionCriteriaRequired\n    lastWrittenCoverLetter @include(if: $isAuthenticated) {\n      content\n      __typename\n    }\n    __typename\n  }\n  __typename\n}\n\nfragment QuestionnaireFragment on JobApplicationProcess {\n  questionnaire {\n    questions @include(if: $isAuthenticated) {\n      id\n      text\n      __typename\n      ... on SingleChoiceQuestion {\n        lastAnswer {\n          id\n          text\n          uri\n          __typename\n        }\n        options {\n          id\n          text\n          uri\n          __typename\n        }\n        __typename\n      }\n      ... on MultipleChoiceQuestion {\n        lastAnswers {\n          id\n          text\n          uri\n          __typename\n        }\n        options {\n          id\n          text\n          uri\n          __typename\n        }\n        __typename\n      }\n      ... on PrivacyPolicyQuestion {\n        url\n        options {\n          id\n          text\n          uri\n          __typename\n        }\n        __typename\n      }\n    }\n    __typename\n  }\n  __typename\n}\n\nfragment JobFragment on Job {\n  id\n  createdAt {\n    shortLabel\n    __typename\n  }\n  content\n  title\n  advertiser {\n    id\n    name(locale: $locale)\n    __typename\n  }\n  abstract\n  source\n  products {\n    branding {\n      id\n      logo {\n        url\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n  tracking {\n    hasRoleRequirements\n    __typename\n  }\n  __typename\n}"}]','[{"operationName":"ApplySubmitApplication","variables":{"input":{"jobId":"'.$job_id.'","correlationId":"'.$corelation_id.'","zone":"asia-4","profilePrivacyLevel":"Standard","resume":{"id":"'.$resume.'","uri":"/v2/blobstore/resumes/'.$resume.'/","idFromResumeResource":-1},"coverLetter":{"writtenText":"'.$coverletters_text.'","uri":"/v2/blobstore/coverletters/'.$coverletters_id.'/"},"mostRecentRole":{"company":"Sekretariat DPRD Kabupaten Lebak","title":"Office Support (Magang Sekolah)","started":{"year":2023,"month":7},"finished":{"year":2023,"month":12}},"questionnaireAnswers":null},"locale":"id-ID"},"query":"mutation ApplySubmitApplication($input: SubmitApplicationInput!, $locale: Locale) {\n  submitApplication(input: $input) {\n    ... on SubmitApplicationSuccess {\n      applicationId\n      __typename\n    }\n    ... on SubmitApplicationFailure {\n      errors {\n        message(locale: $locale)\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}"}]'
    ];
  $graphql_params = [
      json_encode([
          "operationName" => "jobDetailsPersonalised",
          "variables" => [
              "id" => $job_id,
              "languageCode" => "id",
              "locale" => "id-ID",
              "timezone" => "Asia/Jakarta",
              "zone" => "asia-4"
          ],
          "query" => "query jobDetailsPersonalised(\$id: ID!, \$tracking: JobDetailsTrackingInput, \$locale: Locale!, \$zone: Zone!, \$languageCode: LanguageCodeIso!, \$timezone: Timezone!) {\n  jobDetails(id: \$id, tracking: \$tracking) {\n    personalised {\n      isSaved\n      appliedDateTime {\n        longAbsoluteLabel(locale: \$locale, timezone: \$timezone)\n        __typename\n      }\n      topApplicantBadge {\n        label(locale: \$locale)\n        description(locale: \$locale, zone: \$zone)\n        __typename\n      }\n      salaryMatch {\n        ... on JobProfileMissingSalaryPreference {\n          label(locale: \$locale)\n          __typename\n        }\n        ... on JobProfileSalaryMatch {\n          label(locale: \$locale)\n          salaryPreference(locale: \$locale, languageCode: \$languageCode) {\n            id\n            description\n            country {\n              countryCode\n              name\n              __typename\n            }\n            currencyCode\n            amount\n            salaryType\n            __typename\n          }\n          __typename\n        }\n        ... on JobProfileSalaryNoMatch {\n          label(locale: \$locale)\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"
      ]),
      json_encode([
          "operationName" => "GetJobApplicationProcess",
          "variables" => [
              "jobId" => $job_id,
              "isAuthenticated" => true,
              "locale" => "id-ID"
          ],
          "query" => "query GetJobApplicationProcess(\$jobId: ID!, \$isAuthenticated: Boolean!, \$locale: Locale) {\n  jobApplicationProcess(jobId: \$jobId) {\n    ...LocationFragment\n    ...ClassificationFragment\n    ...DocumentsFragment\n    ...QuestionnaireFragment\n    job {\n      ...JobFragment\n      __typename\n    }\n    linkOut\n    extractedRoleTitles\n    __typename\n  }\n}\n\nfragment LocationFragment on JobApplicationProcess {\n  location {\n    id\n    name\n    __typename\n  }\n  state {\n    id\n    __typename\n  }\n  area {\n    id\n    name\n    __typename\n  }\n  __typename\n}\n\nfragment ClassificationFragment on JobApplicationProcess {\n  classification {\n    id\n    name\n    subClassification {\n      id\n      name\n      __typename\n    }\n    __typename\n  }\n  __typename\n}\n\nfragment DocumentsFragment on JobApplicationProcess {\n  documents {\n    lastAppliedResumeIdPrefill @include(if: \$isAuthenticated)\n    selectionCriteriaRequired\n    lastWrittenCoverLetter @include(if: \$isAuthenticated) {\n      content\n      __typename\n    }\n    __typename\n  }\n  __typename\n}\n\nfragment QuestionnaireFragment on JobApplicationProcess {\n  questionnaire {\n    questions @include(if: \$isAuthenticated) {\n      id\n      text\n      __typename\n      ... on SingleChoiceQuestion {\n        lastAnswer {\n          id\n          text\n          uri\n          __typename\n        }\n        options {\n          id\n          text\n          uri\n          __typename\n        }\n        __typename\n      }\n      ... on MultipleChoiceQuestion {\n        lastAnswers {\n          id\n          text\n          uri\n          __typename\n        }\n        options {\n          id\n          text\n          uri\n          __typename\n        }\n        __typename\n      }\n      ... on PrivacyPolicyQuestion {\n        url\n        options {\n          id\n          text\n          uri\n          __typename\n        }\n        __typename\n      }\n    }\n    __typename\n  }\n  __typename\n}\n\nfragment JobFragment on Job {\n  id\n  createdAt {\n    shortLabel\n    __typename\n  }\n  content\n  title\n  advertiser {\n    id\n    name(locale: \$locale)\n    __typename\n  }\n  abstract\n  source\n  products {\n    branding {\n      id\n      logo {\n        url\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n  tracking {\n    hasRoleRequirements\n    __typename\n  }\n  __typename\n}"
          ]),
      json_encode([[
          "operationName" => "ApplySubmitApplication",
          "variables" => [
              "input" => [
                  "jobId" => $job_id,
                  "correlationId" => $corelation_id,
                  "zone" => "asia-4",
                  "profilePrivacyLevel" => "Standard",
                  "resume" => [
                      "id" => $resume,
                      "uri" => "/v2/blobstore/resumes/$resume/",
                      "idFromResumeResource" => -1
                  ],
                  "coverLetter" => [
                      "writtenText" => $coverletters_text,
                      "uri" => "/v2/blobstore/coverletters/$coverletters_id/"
                  ],
                  "mostRecentRole" => [
                      "company" => "Sekretariat DPRD Kabupaten Lebak",
                      "title" => "Office Support (Magang Sekolah)",
                      "started" => [
                          "year" => 2023,
                          "month" => 7
                      ],
                      "finished" => [
                          "year" => 2023,
                          "month" => 12
                      ]
                  ],
                  "questionnaireAnswers" => null
              ],
              "locale" => "id-ID"
          ],
          "query" => "mutation ApplySubmitApplication(\$input: SubmitApplicationInput!, \$locale: Locale) {\n  submitApplication(input: \$input) {\n    ... on SubmitApplicationSuccess {\n      applicationId\n      __typename\n    }\n    ... on SubmitApplicationFailure {\n      errors {\n        message(locale: \$locale)\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}"
      ]])
      ];
  foreach ($graphql_params as $i => $data){
    if ($i == 2 && is_array($input_answers) && !is_null($questionnaire)) {
        $apply_params = json_decode($graphql_params[$i], true);
        $apply_params[0]['variables']['input']['questionnaireAnswers'] = $input_answers;
        $data = json_encode($apply_params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      } else if ($i == 5 && is_null($questionnaire)) {
          $data = $graphql_params[$i];
      }
    $get_graphql = graphql($url_graphql, $data, $auth, $cookie,$user_agent);
    $graphql_code = $get_graphql[0];
    $graphql_response = $get_graphql[1];
    $graphql_decode = json_decode($graphql_response, true);
    if ($i == 0) {
      if(isset($graphql_decode['errors'][0]['extensions']['code'])){
        echo("[token] ".$graphql_decode['errors'][0]['extensions']['code']."\n");
        die();
      }
      $appliedDateTime = $graphql_decode['data']['jobDetails']['personalised']['appliedDateTime'];
      if ($appliedDateTime == null) {
        continue;
      } else if ($appliedDateTime != null && isset($appliedDateTime['longAbsoluteLabel'])) {
        $last_applied = $appliedDateTime['longAbsoluteLabel'];
        echo $job_id . " => " . $job_company . " => " .$job_location . " => "."applied at $last_applied\n";
        break;
      } else {
        echo "error => jobDetailsPersonalised\n";
        die();
      }
    }
    else if ($i == 1){
      $linkOut = $graphql_decode['data']['jobApplicationProcess']['linkOut'];
      if (isset($linkOut) && $linkOut == false){
        $questionnaire = $graphql_decode['data']['jobApplicationProcess']['questionnaire'];
        if (isset($questionnaire['questions'])) {
          $questions = $questionnaire['questions'];
          $input_answers = [];
          foreach ($questions as $question) {
            $id_question = $question['id'];
            $type_question = $question['__typename'];
            $text_question = $question['text'];
            $options_answer = $question['options'];
            $sum_of_key1 = calculateKeyOccurrences($text_question, $key1);
            $sum_of_key2 = calculateKeyOccurrences($text_question, $key2);
            if (isset($question['lastAnswer'])) {
                $answer = [$question['lastAnswer']];
            } elseif ($sum_of_key1 >= 1 && $type_question == 'SingleChoiceQuestion') {
                $answer = [$options_answer[1]];
            } else {
                $answer = [end($options_answer)];
            }
        
            if (is_array($answer)) {
                foreach ($answer as &$element) {
                    unset($element['__typename']);
                }
            }
            $input_answers[] = [
                "questionId" => $id_question,
                "answers" => $answer
            ];
          }
        }
      }
      else if (!isset($linkOut) || $linkOut == true){
        echo $job_id . " => " . $job_company . " => " .$job_location . " => "."linkOut\n";
        break;
      }
      else {
        echo "error => GetJobApplicationProcess\n";
        die();
      }
    }
    else if ($i == 2) {
      if (isset($graphql_decode[0]['data']['submitApplication']['__typename'])) {
        $apply_info = $graphql_decode[0]['data']['submitApplication']['__typename'];
        echo $job_id . " => " . $job_company . " => " .$job_location . " => "."$apply_info\n";
      } else {
        echo "error => ApplySubmitApplication\n";
        die();
      }
    }
  }
}
echo("\n");
for($c=$refreshJobs;$c > 0;$c--){

  echo "\r                                         \r";

  echo "[tunggu] => mohon tunggu selama $c lagi";
  sleep(1);
  if($c==1){
    echo "\r                                         \r";
  }
}
}