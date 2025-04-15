<template>
  <div class="md-layout">
    <div class="md-layout-item md-size-100">
      <widget title="Customer Detail">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item"
            :class="showCustomerInformation ? 'md-size-20' : 'md-size-100'"
            align="center"
          >
            <md-icon class="md-size-4x">account_circle</md-icon>
            <h1>
              {{ this.person.title }} {{ this.person.name }}
              {{ this.person.surname }}
            </h1>
          </div>
          <div class="md-layout-item md-size-80" v-if="showCustomerInformation">
            <div class="md-layout-item md-layout md-size-100">
              <div
                class="md-layout-item md-layout md-gutter md-size-100"
                style="margin-bottom: 3vh"
              >
                <div class="md-layout-item md-size-35">
                  <h4>
                    <md-icon>wc</md-icon>
                    {{ $tc("words.gender") }}:{{ this.person.gender }}
                  </h4>
                </div>
                <div class="md-layout-item md-size-35">
                  <h4>
                    <md-icon>school</md-icon>
                    &nbsp;{{ $tc("words.education") }}:
                    {{ this.person.education }}
                  </h4>
                </div>

                <div class="md-layout-item md-size-30">
                  <h4>
                    <md-icon>cake</md-icon>
                    &nbsp;{{ $tc("words.birthday") }}:
                    {{ this.person.birthDate }}
                  </h4>
                </div>
              </div>
              <div
                class="md-layout-item md-layout md-gutter md-size-100"
                v-if="person.addresses.length > 0"
              >
                <div class="md-layout-item md-size-35">
                  <h4>
                    <md-icon>email</md-icon>
                    &nbsp;{{ $tc("words.email") }}:
                    {{ person.addresses[0].email }}
                  </h4>
                </div>
                <div class="md-layout-item md-size-35">
                  <h4>
                    <md-icon>phone</md-icon>
                    &nbsp;{{ $tc("words.phone") }}:
                    {{ person.addresses[0].phone }}
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </widget>
    </div>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { PersonService } from "@/services/PersonService"
import { notify } from "@/mixins/notify"

export default {
  name: "ClientDetailCard",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      personService: new PersonService(),
      person: {},
    }
  },
  props: {
    personId: {
      required: true,
    },
    showCustomerInformation: {
      type: Boolean,
      default: true,
    },
  },
  created() {
    this.getPersonDetail(this.personId)
  },
  methods: {
    async getPersonDetail(personId) {
      try {
        this.person = await this.personService.getPerson(personId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped></style>
