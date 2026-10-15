<template>
  <div>
    <div class="wide-screen-sms-list md-layout md-gutter">
      <div
        class="md-layout-item md-size-30 md-medium-size-40 md-small-size-100"
        v-if="checkScreen('numberList')"
      >
        <widget :subscriber="subscriber" :paginator="smsService.paginator">
          <div class="sticky">
            <md-field>
              <md-input
                v-model="filterNumber"
                type="text"
                class="form-control"
                :placeholder="$tc('words.search')"
              ></md-input>
            </md-field>
          </div>
          <div class="sms-scrollable">
            <md-table>
              <md-table-row
                v-for="sms in numberList"
                :key="sms.number"
                style="cursor: pointer"
                @click="smsDetail(sms.number)"
              >
                <md-table-cell
                  :class="sms.number === selectedNumber ? 'active' : ''"
                >
                  <div class="md-layout md-gutter">
                    <div class="md-layout-item md-size-30" v-if="sms.owner">
                      <img
                        :data-letters="sms.owner.name[0] + sms.owner.surname[0]"
                        src=""
                        alt=""
                      />
                    </div>
                    <div class="md-layout-item md-size-30" v-else>
                      <md-icon class="person-icon">person</md-icon>
                    </div>
                    <div class="md-layout-item md-size-55">
                      <div class="md-layout md-layout-item md-size-100">
                        <div
                          class="md-layout-item md-size-100 sms-owner"
                          v-if="sms.owner"
                        >
                          {{ sms.owner.name }}
                          {{ sms.owner.surname }}
                        </div>
                        <div class="md-layout-item md-size-100" v-else>---</div>
                        <div class="md-layout-item md-size-100">
                          <small>
                            {{ sms.number }}
                          </small>
                        </div>
                      </div>
                    </div>
                    <div class="md-layout-item md-size-15">
                      <small class="sms-total">
                        {{ sms.total }}
                      </small>
                    </div>
                  </div>
                </md-table-cell>
              </md-table-row>
            </md-table>
          </div>
          <!-- Sms Number List -->
        </widget>
      </div>
      <div
        class="md-layout-item md-size-70 md-medium-size-60 md-small-size-100"
        v-if="checkScreen('detail')"
      >
        <widget>
          <div class="sticky sms-detail-head">
            <div class="md-layout-item md-layout md-gutter">
              <div class="md-layout-item md-size-95">
                <md-icon>perm_phone_msg</md-icon>
                {{ selectedNumber }}
              </div>
              <div class="md-layout-item md-size-5">
                <md-icon v-if="!isMobile">sms</md-icon>
                <md-button
                  class="md-icon-button"
                  v-if="isMobile"
                  @click="showNumberList = true"
                >
                  <md-icon>reply</md-icon>
                </md-button>
              </div>
            </div>
            <hr />
          </div>
          <div class="sms-detail-scrollable">
            <div class="md-layout" v-for="sms in list" :key="sms.id">
              <div class="md-layout-item md-layout md-size-100 md-gutter">
                <div
                  class="md-layout-item md-size-60"
                  v-if="sms.direction === 0"
                >
                  <div class="md-layout-item md-layout md-gutter">
                    <div class="sms-body-triangle left-arrow"></div>
                    <div class="md-layout-item sms-body sms-body-left">
                      {{ sms.body }}
                    </div>
                  </div>
                </div>
                <div class="md-layout-item md-size-40"></div>
                <div
                  class="md-layout-item md-size-60"
                  v-if="sms.direction === 1"
                >
                  <div class="md-layout-item md-layout md-gutter">
                    <div class="md-layout-item sms-body">
                      {{ sms.body }}
                    </div>
                    <div class="sms-body-triangle right-arrow"></div>
                  </div>
                </div>

                <div class="md-layout-item md-size-100">
                  <small
                    :class="
                      sms.direction === 1
                        ? 'created-date-right'
                        : 'created-date-left'
                    "
                  >
                    <span
                      class="channel-indicator"
                      :title="getChannel(sms)"
                      :aria-label="getChannel(sms)"
                    >
                      <svg
                        v-if="getChannel(sms) === 'WhatsApp'"
                        class="channel-logo-svg"
                        viewBox="350 320 1800 1800"
                        aria-hidden="true"
                      >
                        <g fill="currentColor" fill-rule="evenodd">
                          <path
                            d="M1912.32,591.45C1743.07,422.05,1518,328.72,1278.17,328.62c-494.13,0-896.28,402-896.48,896.14a894.32,894.32,0,0,0,119.66,448l-127.18,464.4,475.24-124.62a896.07,896.07,0,0,0,428.4,109.08h.37c494.07,0,896.27-402.05,896.47-896.18.09-239.46-93.07-464.63-262.33-634ZM1278.18,1970.3h-.31a744.11,744.11,0,0,1-379.24-103.83l-27.21-16.14-282,74,75.27-274.87L647,1621.23A742.88,742.88,0,0,1,533,1224.81C533.2,814.12,867.46,480,1278.47,480c199,.08,386.1,77.66,526.78,218.46s218.11,327.94,218,527c-.16,410.73-334.41,744.89-745.1,744.89Z"
                          />
                          <path
                            d="M977.6,1658.86c6.31,3.8,19.27,3.8,49,3.77,126.15-.11,235.11-.49,312.62-.49,361.71,0,352.2-380.79,183.08-428.3,24.77-43.88,137.63-126.32,67.83-296.72-69-168.49-365.85-130.25-568.86-130.15-75.12,0-63.88,55.51-63.5,141.81.62,136.69.11,506.67,0,666.61C957.77,1647.81,967.56,1652.79,977.6,1658.86Zm156.51-139.55c34.15,0,114.71,0,183.77-.11,78.2-.12,147.83-36.7,146.09-114.55-1.27-73.33-50.06-97.4-117.78-104.12-64.5.62-138.27.62-212.08.62v218.16Zm0-365.44c136.05-1.87,188.54,5.48,262.82-13.12,51-29,73.34-136.42.29-172.92-50.75-25.35-200.71-16.68-263.11-14.08v200.12Z"
                          />
                        </g>
                      </svg>
                      <md-icon
                        v-else-if="getChannel(sms) === 'SMS'"
                        class="channel-logo-md"
                      >
                        sms
                      </md-icon>
                      <md-icon v-else class="channel-logo-md">forum</md-icon>
                    </span>
                    <md-icon>schedule</md-icon>
                    {{ formatDate(sms.created_at) }} -
                    {{ getTimeAgo(sms.created_at) }}
                    <sms-delivery-status
                      v-if="sms.direction === 1"
                      :status="sms.status"
                      :error-message="sms.error_message"
                    />
                  </small>
                </div>
              </div>
            </div>
          </div>
          <div class="md-layout md-gutter message-area">
            <div class="md-layout-item md-size-100">
              <md-field :class="{ 'md-invalid': errors.has('message') }">
                <label>{{ $tc("phrases.messageText") }}</label>
                <md-textarea
                  v-model="message"
                  style="min-height: 75px !important"
                  id="message"
                  name="message"
                  v-validate="'required|max:160|min:3'"
                />
                <span class="md-error">
                  {{ errors.first("message") }}
                </span>
              </md-field>
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
            </div>
            <div class="md-layout-item md-size-100">
              <md-button
                @click="sendSms"
                :disabled="loading || selectedNumber === ''"
                class="md-raised md-primary send-button"
              >
                {{ $tc("words.send") }}
              </md-button>
            </div>
          </div>
        </widget>
      </div>
      <!-- Sms Detail  -->
    </div>
  </div>
</template>

<script>
import moment from "moment"

import { notify } from "@/mixins/notify.js"
import { SmsService } from "@/services/SmsService.js"
import { EventBus } from "@/shared/eventbus.js"
import SmsDeliveryStatus from "@/shared/SmsDeliveryStatus.vue"
import Widget from "@/shared/Widget.vue"

const debounce = require("debounce")

export default {
  name: "List",
  mixins: [notify],
  components: { SmsDeliveryStatus, Widget },
  watch: {
    filterNumber: debounce(function () {
      this.searchSms(this.filterNumber)
    }, 400),
  },

  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    this.senderId =
      this.$store.getters["auth/authenticationService"].authenticateUser.id
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  data() {
    return {
      smsService: new SmsService(),
      list: [],
      numberList: [],
      filterNumber: "",
      subscriber: "smsList",
      selectedNumber: "",
      showNumberList: true,
      senderId: "",
      message: "",
      headers: [],
      tableName: "SMS",
      loading: false,
      windowWidth: 0,
    }
  },
  computed: {
    isMobile() {
      return this.$store.getters["resolution/getDevice"]
    },
  },
  methods: {
    checkScreen(type) {
      let x
      if (this.isMobile === false) {
        x = true
      } else {
        if (this.showNumberList === true) {
          if (type === "numberList") {
            x = true
          }
          if (type === "detail") {
            x = false
          }
        } else {
          if (type === "numberList") {
            x = false
          }
          if (type === "detail") {
            x = true
          }
        }
      }

      return x
    },
    getTimeAgo(date) {
      return moment(date).fromNow()
    },
    formatDate(date) {
      let d = new Date(date)
      return d.toLocaleDateString()
    },
    async reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.numberList = await this.smsService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.numberList.length,
      )
    },
    async smsDetail(phone) {
      this.showNumberList = false
      this.selectedNumber = phone
      this.list = await this.smsService.getDetail(phone)
    },
    async sendSms() {
      const validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.smsService.sendToNumber(
          "person",
          this.message,
          this.selectedNumber,
          this.senderId,
        )
        this.loading = false
        this.alertNotify("success", this.$tc("phrases.smsListNotify", 2))
        this.message = ""
        await this.smsDetail(this.selectedNumber)
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
      this.$validator.reset()
    },
    searchSms(text) {
      const term = (text ?? "").trim()
      if (term.length === 0) {
        this.smsService.showAll()

        return
      }
      this.smsService.search(term)
    },
    getChannel(sms) {
      if (
        sms.gateway_id === 33 ||
        (typeof sms.uuid === "string" && sms.uuid.startsWith("wamid"))
      ) {
        return "WhatsApp"
      }
      if (sms.gateway_id === 10) {
        return "Viber"
      }
      return "SMS"
    },
  },
}
</script>

<style scoped lang="scss">
[data-letters]:before {
  content: attr(data-letters);
  display: inline-block;
  font-size: 1em;
  width: 2.5em;
  height: 2.5em;
  line-height: 2.5em;
  text-align: center;
  border-radius: 50%;
  background: #313131;
  vertical-align: middle;
  margin-right: 1em;
  color: white;
}

.person-icon {
  width: 1.3em;
  height: 1.3em;
  text-align: center;
  border-radius: 50%;
  background: #313131;
  vertical-align: middle;
  margin-right: 1em;
  color: white !important;
}

td.active {
  background-color: #e2f3fd !important;
}

.sticky {
  position: sticky;
  top: 0;
  height: 20% !important;
}

.sms-detail-head {
  margin-top: 23px;
}

.sms-scrollable {
  overflow: auto;
  max-height: 73vh;
}

.sms-detail-scrollable {
  overflow: auto;
  height: 60vh;
  max-height: 60vh;
}

.created-date-right {
  float: right;
  margin-right: 5px;
}

.created-date-left {
  float: left;
  margin-left: 5px;
}

.sms-body {
  padding: 20px;
  background-color: #e2f3fd;
  margin: 10px;
  d-webkit-border-radius: 10px;
  -moz-border-radius: 10px;
  border-radius: 10px;
}

.sms-body-left {
  background-color: #ffebee !important;
}

.sms-body-triangle {
  width: 0;
  height: 0;
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  margin-top: 40px;
}

.right-arrow {
  border-left: 8px solid #e2f3fd;
  margin-left: -10px;
  margin-right: -20px;
}

.left-arrow {
  border-right: 8px solid #ffebee;
  margin-right: -10px;
  margin-left: -20px;
}

.send-button {
  width: 10vw;
  right: 0;
  float: right;
}

.message-area {
  margin-top: 2vh;
}

.sms-total {
  position: absolute;
  right: 1vw;
  background-color: #f2622d;
  text-align: center;
  border-radius: 50%;
  vertical-align: middle;
  color: whitesmoke;
  width: 1.5em;
  height: 1.5em;
}

.sms-owner {
  font-weight: 500;
}

.channel-indicator {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  vertical-align: middle;
  margin-right: 6px;
  color: rgba(0, 0, 0, 0.54);
}

.channel-logo-svg {
  width: 18px;
  height: 18px;
  fill: currentColor;
  display: inline-block;
  vertical-align: middle;
}

.channel-logo-md {
  font-size: 18px !important;
  width: 18px !important;
  height: 18px !important;
  min-width: 18px !important;
  color: rgba(0, 0, 0, 0.54) !important;
  vertical-align: middle;
}
</style>
