<?php
    add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/personalInformation/personal-information.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/personalInformation/assets/fonts/index.css">', 0);
    add_javascript('<script src="'.$member_skin_url.'/personalInformation/personal-information.js"></script>', 1);
?>
<script>
    window.firstVisitAgree = {
        token : <?php echo (!empty($ckey))?"'".$ckey."'":'false'; ?>,
        type : <?php echo ($firstVisitAgree == 'Y')?'"Y"':'"N"'; ?>
    };
</script>
  <div class="personal-information-container">
    <div class="personal-information-wrap">
      <div class="personal-information">
        <div class="personal-information-top-area">
          <div class="personal-information-top-inner">
            <div class="bb-market-logo-wrap">
              <img src="<?php echo $member_skin_url.'/personalInformation/assets/'; ?>bb-market-logo.png" alt="bb-market-logo" class="bb-market-logo">
            </div>
            <div class="personal-information-title">
              개인정보 제 3자 제공 동의
            </div>
            <div class="personal-information-subtitle">
              (주)한스바이오텍은 서비스 내 이용자 식별 및 서비스 제공을 위해 개인정보를 제공합니다.
            </div>

            <div class="personal-information-detail">
              <div class="personal-information-detail-item">
                <div class="detail-left">
                    제공받는자 :
                </div>
                <div class="detail-right">
                    베리베리스마켓
                </div>
              </div>

              <div class="personal-information-detail-item">
                <div class="detail-left">
                  제공 목적 :
                </div>
                <div class="detail-right">
                  CTC Wallet 계정 연결을 통한 이용자 식별 및 베리베리스마켓 서비스 제공
                </div>
              </div>

              <div class="personal-information-detail-item">
                <div class="detail-left">
                  보유기간 :
                </div>
                <div class="detail-right">
                  개인정보 제3자 제공 동의시부터 서비스 탈퇴시
                </div>
              </div>

              <div class="personal-information-detail-item">
                <div class="detail-left">
                  필수제공항목 :
                </div>
                <div class="detail-right">
                  이름, 계정정보(휴대폰번호/이메일주소)
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="personal-information-bottom-area">
          <button class="disagree-button">
            동의안함
          </button>
          <button class="agree-button">
            확인
          </button>
        </div>
      </div>
    </div>
  </div>