<template>
  <div>
    <md-table>
      <md-table-row>
        <md-table-head></md-table-head>
        <md-table-head v-for="head in tableHeads" :key="head">
          {{ head }}
        </md-table-head>
      </md-table-row>
      <template v-for="(ticket, index) in ticketList">
        <md-table-row @click="openTicket(index)" :key="'tic' + index">
          <md-table-cell>
            <md-icon>
              {{
                showTicket === index
                  ? "keyboard_arrow_down"
                  : "keyboard_arrow_right"
              }}
            </md-icon>
          </md-table-cell>
          <md-table-cell>{{ ticket.title }}</md-table-cell>
          <md-table-cell v-if="ticket.category">
            {{ ticket.category }}
          </md-table-cell>
          <md-table-cell v-else>-</md-table-cell>
          <md-table-cell v-if="!allowLock">
            <span :class="[!ticket.closed ? 'open-ticket' : 'closed-ticket']">
              {{ !ticket.closed ? "Open" : "Closed" }}
            </span>
          </md-table-cell>
          <md-table-cell>
            {{ formatDate(ticket.created) }}
          </md-table-cell>
        </md-table-row>
        <md-table-row v-if="showTicket === index" :key="index">
          <md-table-cell :colspan="tableHeads.length + 1">
            <hr
              :class="[!ticket.closed ? 'open-ticket-hr' : 'close-ticket-hr']"
            />
            <div class="ticket-desc">
              <div class="md-layout md-gutter md-size-100">
                <div class="md-layout-item md-size-100">
                  <span class="md-subheader">
                    {{ $tc("phrases.ticketDetails") }}
                  </span>
                </div>
              </div>
              <div class="md-layout md-gutter md-size-100">
                <div class="md-layout-item md-size-70">
                  <span v-if="ticket.assignedTo">
                    <b>
                      {{ $tc("phrases.assignTo", 2) }}:
                      {{ ticket.assignedTo.user_name }}
                    </b>
                  </span>
                </div>

                <div
                  class="md-layout-item md-size-30"
                  @click="lockTicket(ticket)"
                  style="cursor: pointer"
                  v-if="!ticket.closed && allowLock"
                >
                  <md-icon style="float: right !important; color: #9a0325">
                    lock
                  </md-icon>
                </div>
              </div>

              <div class="md-layout-item md-size-100 t-text-area">
                <md-icon>person</md-icon>
                <span
                  v-if="ticket.owner !== undefined && ticket.owner !== null"
                >
                  {{ ticket.owner.name }}
                  {{ ticket.owner.surname }} :
                </span>
                <p class="t-text" v-text="ticket.description"></p>
              </div>
              <div class="md-layout-item md-size-100" style="min-height: 25px">
                <em class="pull-right-label-primary" style="cursor: pointer">
                  <small @click="showComments = !showComments">Comments</small>
                  {{ ticket.comments.length }}
                </em>
              </div>
              <div class="clear-fix"></div>

              <div class="md-layout-item md-size-100">
                <div v-if="showComments">
                  <div
                    class="comment-item"
                    v-for="(comment, index) in ticket.comments"
                    :key="'com' + index"
                  >
                    <md-icon>person</md-icon>
                    {{ comment.username }} wrote
                    <br />
                    {{ comment.comment }}
                    <br />
                    <md-icon>access_time</md-icon>
                    <small>
                      {{ getTimeAgo(comment.date) }}
                    </small>
                    <div class="clearfix"></div>
                  </div>
                </div>
              </div>
              <div
                class="md-layout-item md-size-95 new-comment-area"
                v-if="showComments"
              >
                <md-field>
                  <label>
                    {{ $tc("phrases.newComment") }}
                  </label>
                  <md-textarea md-autogrow v-model="newComment"></md-textarea>
                  <md-button
                    type="submit"
                    class="md-primary md-dense"
                    @click="sendComment(ticket)"
                  >
                    {{ $tc("words.save") }}
                  </md-button>
                </md-field>
              </div>
            </div>
          </md-table-cell>
        </md-table-row>
      </template>
    </md-table>
  </div>
</template>

<script>
import { TicketCommentService } from "@/services/TicketCommentService"
import { EventBus } from "./eventbus"
import { SmsService } from "@/services/SmsService"
import { TicketService } from "@/services/TicketService"
import moment from "moment"
import { notify } from "@/mixins/notify"

export default {
  name: "TicketItem",
  mixins: [notify],
  props: {
    ticket: String,
    allowComment: Boolean,
    ticketList: Array,
    tableHeads: Array,
    allowLock: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      ticketCommentService: new TicketCommentService(),
      ticketService: new TicketService(),
      smsService: new SmsService(),
      showComments: false,
      newComment: "",
      showTicket: null,
      senderId:
        this.$store.getters["auth/authenticationService"].authenticateUser.id,
    }
  },
  mounted() {
    console.log("Mounted this ticket list:", this.ticketList)
  },
  methods: {
    getTimeAgo(date) {
      return moment(date).fromNow()
    },
    formatDate(date) {
      let d = new Date(date)
      return d.toLocaleDateString()
    },
    openTicket(index) {
      if (this.showTicket === index) {
        this.showTicket = null
      } else {
        this.showTicket = index
      }
    },
    navigateToOwner(id) {
      this.$router.push({ path: "/people/" + id })
    },
    async lockTicket(ticket) {
      try {
        await this.ticketService.closeTicket(ticket.id)
        EventBus.$emit("listChanged")
        this.alertNotify("success", this.$tc("phrases.ticketNotify", 1))
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },

    async sendComment(ticket) {
      try {
        let name =
          this.$store.getters["auth/authenticationService"].authenticateUser
            .name
        let username =
          this.$store.getters["auth/authenticationService"].authenticateUser
            .email
        let newComment = await this.ticketCommentService.createComment(
          this.newComment,
          ticket.id,
          name,
          username,
        )
        if (ticket.category !== null && ticket.category.out_source) {
          await this.smsService.sendToPerson(
            this.newComment,
            ticket.owner.id,
            this.senderId,
          )
        }
        this.showComments = false
        EventBus.$emit("listChanged")
        this.alertNotify("success", this.$tc("phrases.ticketNotify", 2))
        ticket.comments.push(newComment)
        this.showComments = false
        this.newComment = null
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped>
.new-comment-area {
  margin-top: 30px;
  margin-left: 30px !important;
}

.comment-item {
  margin-top: 10px;
  border-width: 1px;
  border-style: solid;
  background-color: rgba(242, 248, 255, 0.79);
  margin-left: 30px;
  padding: 10px;
  white-space: initial;
}

.ticket-area {
  border: 0.05px solid lightgray;
  margin-top: 0.4rem;
}

.ticket-area:hover {
  background-color: #f3f3f3;
}

.comment-box {
  background-color: #f4fff0;
  border-width: 1px;
  border-style: dotted;
  padding: 10px;
}

.t-text-area {
  min-width: 100% !important;
  padding: 5px;
  border-style: solid;
  border-width: 1px;
  border-color: #9aa7af;
}

.t-text {
  min-width: 90%;
  white-space: initial;
}

.t-date {
  font-size: x-small;
  color: #2a2a2a;
  float: right;
}

.pull-right-label-primary {
  background-color: #3276b1;
  float: right;
  color: white;
  margin: 2vh;
  padding: 0.2em 0.6em 0.3em;
  font-size: 75%;
  font-weight: 700;
  line-height: 1;
  color: #fff;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25em;
}

.Ticket-Are-Grid {
  width: 100%;
}

.Ticket-Area-Row-P {
  font-size: small;
  white-space: initial;
  max-width: 100%;
}

.new-ticket-modal-container {
  padding: 2rem;
  overflow-y: scroll;
}

.ticket-desc {
  margin-bottom: 5vh;
}

.open-ticket-hr {
  height: 1vh;
  background-color: #8eb18e;
  width: 100% !important;
}

.close-ticket-hr {
  height: 1vh;
  background-color: #9a0325;
  width: 100% !important;
}

.expand-ticket {
  padding-right: 5px !important;
  max-width: 10% !important;
}

.closed-ticket {
  background-color: #9d0006;
  color: white;
  padding: 10px;
}

.open-ticket {
  background-color: #0aa66e;
  padding: 10px;
  color: white;
}
</style>
