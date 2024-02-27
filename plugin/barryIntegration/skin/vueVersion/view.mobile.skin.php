<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
    
/*
*
*
*   view.mobile.skin.php
*
*   결제가 필요한 주문건이면, plugin에서 class를 불러온다.. class에서는 지정된 hidden input에서 전송 할 데이터를 가져오는데,
*   vue page에서는 지정된 hidden input이 없으니, 지정 hidden input -> class input -> vue에서 다시 가져오는 불필요한 뎁스가 생김..
* 
*   리뉴얼에선 통합 할 예정...
*/
    //CTC WALLET 결제 관련 CLASS
    include_once(G5_PLUGIN_PATH.'/barryCtcWallet/CtcWallet.php');
    use barry\wallet\CtcWallet as ctcWallet;

    add_stylesheet('<link rel="stylesheet" href="'.$integration_skin_url.'/css/common.mobile.css">', 0);
    add_javascript('<script src="'.$integration_skin_url.'/js/formdata.min.js"></script>', 1);//FormData polyfill IE11 
    add_javascript('<script src="'.$integration_skin_url.'/js/polyfill.min.js"></script>', 1);//Promise polyfill IE11 
?>
<script>
    //Integration view Javascript 전역 변수
    orderId = <?php echo $id ?>;// CTC Wallet password global variable
    barry_view_wr_id = <?php echo $id ?>;
    barry_view_target_bo_table = '<?php echo $target_bo_table ?>';
</script>
    <!-- 오류를 피하기 위해 함수로 선언 하지 않습니다. -->
<!--    <script src="--><?php //echo $integration_skin_url; ?><!--/js/vue.min.js"></script>-->
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="<?php echo $integration_skin_url; ?>/js/axios.min.js"></script>
    <div id=app></div><script type=text/javascript src=<?php echo $integration_skin_url; ?>/static/js/manifest.2ae2e69a05c33dfc65f8.js></script><script type=text/javascript src=<?php echo $integration_skin_url; ?>/static/js/vendor.5b6e2ba0d2a9f6121b71.js></script><script type=text/javascript src=<?php echo $integration_skin_url; ?>/static/js/app.431af1716559edc84169.js></script>
    <article id="integrationMobileApp" class="contentsWrap">
        <div class="contents container-fluid">
            <form id="integrationMobileAppViewForm" method="post" enctype="multipart/form-data" v-for="(data, dataIndex) in integrationList" v-bind:key="data.id">
                <div class="card mb-3">
                    <div class="row no-gutters">
                        <div class="col-4">
                            <img class="card-img" alt="상품 이미지" v-bind:src="data.imgSrc">
                        </div>
                        <div class="col-8">
                            <div class="card-body">
                                <h5 class="card-title">{{data.itemSubject}}</h5>
                                <p class="card-text">
                                    {{data.itemContent}}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <section class="info">
                    <div class="title"> 
                        <h3>상품 정보</h3>
                    </div>
                    <ul class="contents left">
                        <li>
                            <h4>판매자</h4>
                            <span>{{data.sellerName}}({{data.sellerTelNumber}})</span>
                        </li>
                        <li>
                            <h4>주문 번호</h4>
                            <span>{{data.id}}</span>
                        </li>
                        <li>
                            <h4>주문 일시</h4>
                            <span>{{data.orderDate}}</span>
                        </li>
                        <li>
                            <h4>상품명</h4>
                            <span>블라블라</span>
                        </li>
                        <li>
                            <h4>상품금액</h4>
                            <span>
                                {{data.itemCartPrice | currency}}
                                <span class="unit">{{data.paymentType}}</span>
                            </span>
                        </li>
                        <li>
                            <h4>결제수단</h4>
                            <span>{{data.paymentType}}</span>
                        </li>
                        <li v-if="data.itemSelectOption">
                            <h4>선택 옵션</h4>
                            <span>{{data.itemSelectOption}}</span>
                        </li>
                        <li v-if="data.itemSelectOption">
                            <h4>선택 옵션 추가금액</h4>
                            <span>{{data.itemSelectOptionPrice}}
                                <span class="unit">{{data.paymentType}}</span>
                            </span>
                        </li>
                    </ul>
                </section>
                <section class="info">
                    <div class="title"> 
                        <h3>주문자 정보</h3>
                    </div>
                    <ul class="contents left">
                        <li>
                            <h4>주문자</h4>
                            <span>{{data.buyerName}}</span>
                        </li>
                        <li>
                            <h4>주문자 연락처</h4>
                            <span>{{data.buyerTelNumber}}</span>
                        </li>
                    </ul>
                </section>
                <section class="info">
                    <div class="title"> 
                        <h3>배송지 정보</h3>
                    </div>
                    <ul class="contents left">
                        <li>
                            <h4 class="text-muted">수령인</h4>
                            <span>{{data.receiverName}}</span>
                        </li>
                        <li>
                            <h4 class="text-muted">수령인 연락처</h4>
                            <span>{{data.receiverTelNumber}}</span>
                        </li>
                        <li>
                            <h4 class="text-muted">배송지</h4>
                            <span>{{data.receiverAddress}}</span>
                        </li>
                    </ul>
                </section>
                <section class="info">
                    <div class="title"> 
                        <h3>주문금액</h3>
                        <span class="total">
                            {{(data.itemCartTotalPrice) | currency}}
                            <span class="unit">{{data.paymentType}}</span>
                        </span>
                    </div>
                    <ul class="contents">
                        <li>
                            <h4>상품금액</h4>
                            <span>
                                {{data.itemCartPrice | currency}}
                                <span class="unit">{{data.paymentType}}</span>
                            </span>
                        </li>
                        <li>
                            <h4>선택 옵션 추가금액</h4>
                            <span>
                                {{data.itemSelectOptionPrice | currency}}
                                <span class="unit">{{data.paymentType}}</span>
                            </span>
                        </li>
                        <li>
                            <h4>수량</h4>
                            <span>
                                {{data.qty}}
                                <span class="unit">개</span>
                            </span>
                        </li>
                    </ul>
                </section>
                <section class="info" >
                    <div class="title">
                        <h3>결제 정보</h3>
                    </div>
                    <ul class="contents left">
                        <li>
                            <h4>결제 상태</h4>
                            <span v-if="data.paymentStatus != 'completePayment'">
                                <span class="badge badge-warning">결제 대기</span>
                            </span>
                            <span v-if="data.paymentStatus == 'completePayment'">
                                <span class="badge badge-success">결제 완료</span>
                            </span>
                        </li>
                        <li>
                            <span class="btn btn-primary btn-block" v-on:click="paymentModalShow()" v-if="data.paymentStatus != 'completePayment'">결제</span>
                        </li>
                    </ul>
                </section>
                <section class="info" v-if="data.paymentRealType == 'KRW-TEMPTEMP'">
                    <div class="title"> 
                        <h3>판매자 지갑 주소</h3>
                    </div>
                    <ul class="contents">
                        <li class="sellerWallet">
                            <img class="card-img" alt="판매자 QR 이미지" v-bind:src="data.sellerWalletQrAddress">
                        </li>
                        <li id="sellerWalletAddress" class="sellerWallet" v-bind:data-sellerWalletAddress.trim="data.sellerWalletAddress">
                            {{data.sellerWalletAddress}}
                        </li>
                        <li>
                            <div class="btn btn-dark otherCommand" onClick="copyToClipboard('sellerWalletAddress')">
                                주소복사
                            </div>
                        </li>
                        <li>
                            <a href="https://cybertronchain.com/wallet2/index.php" class="btn btn-dark otherCommand">CTC 지갑가기</a>
                        </li>
                    </ul>
                </section> 
            </form>
        </div>
    </article>


    <article calss="otherArea">
        <!-- 주문 view 페이지와 똑같이 맞추기 위해, 부득이하게 hidden을 추가합니다.. 리뉴얼에서는, hidden type을 통한 데이터 전송을 자제해야..-->
        <input type="hidden" name="sellwallet" value="">
        <input type="hidden" name="phone" value="">
        <div id="paymentLayer" class="modal paymentLayer" tabindex="1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <form id="paymentform" name="paymentform" method="post">
                            <?php
                                $ctcWallet = ctcWallet::singletonMethod();
                                $ctcWallet->init('basic');
                                //plainPassword, sellerAdress, orderer PhoneNumber만 넘기면 API에서 조회,
                                $ctcWallet->getTransferPasswordCheckFormBuild();
                            ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </article>

<script>
var integrationMobileApp = new Vue({
    el: '#integrationMobileApp',
    data: {
        integrationList: [
        
        ],
    },
    filters: {
        currency: function (value) {
            var num = new Number(value);
            return num.toFixed(0).replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1,")
        }
    },
    created: function () {
        var childThis = this;
        axios.post('<?php echo G5_PLUGIN_URL; ?>/barryIntegration/viewAjax.php', {
            wr_id: barry_view_wr_id,
            barry_view_target_bo_table: barry_view_target_bo_table
        },  
        {
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
            }
        })
        .then(function(res){
            //여러 데이터 중 한개가 실패 할 경우는? 분기? 일단 맨 첫번째 응답값만 정상이면 모두 정상으로 간주
            if(res.data[0].code == 200){
                //ok
                for(i=0;i<=res.data.length-1;i++){
                    childThis.integrationList.push(res.data[i]);
                }
            }
            else{
                //fail
            }
        }).catch(function(e){
            console.error(e);
        });
    },
    mounted: function () {
        this.$nextTick(function () {
            /* Jquery */
            $("#btn_go_wallet").on("click", function() {
                document.location.href = 'https://cybertronchain.com/wallet2/index.php';
            });
            $("#sellerWalletAddressCopy").on("click", function() {
                $(this).select();
                document.execCommand("copy");
            });

            /* Narmal JS 함수 목록 */
            function copyToClipboard(elementId) {
                var aux = document.createElement("input");
                aux.setAttribute("value", document.getElementById(elementId).getAttribute('data-sellerWalletAddress'));
                document.body.appendChild(aux);
                aux.select();
                document.execCommand("copy");
                document.body.removeChild(aux);
            }

        })
    },
    methods: {
        paymentModalShow: function () {
            $('input[name="sellwallet"]').val(this.integrationList[0].sellerWalletAddress);
            $('input[name="phone"]').val(this.integrationList[0].buyerTelNumber);
            $.confirm({
                title: '경고!',
                content: this.integrationList[0].sellerName+'님에게 '+this.integrationList[0].itemCartTotalPrice+' '+this.integrationList[0].paymentType+'를 보내시겠습니까?',
                buttons: {
                    cancel:{
                        text: '취소',
                        btnClass: 'btn btn-dark',
                        action : function () {
                            //cancel........
                        }
                    },
                    confirm:{
                        text: '확인',
                        btnClass: 'btn btn-success',
                        action : function () {
                            $('#paymentLayer').modal('show');
                        }
                    },
                }
            });

        }
    }
})

</script>