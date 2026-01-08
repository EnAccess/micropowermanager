<template>
  <widget title="Customer Detail">
    <md-card>
      <md-card-content>
        <div class="md-layout md-gutter md-alignment-center">
          <div class="md-layout-item md-size-20">
            <md-avatar class="md-avatar-icon md-large">
              {{ initials }}
            </md-avatar>
          </div>
          <div class="md-layout-item">
            <h2>
              {{ this.person.title }}
              {{ this.person.name }}
              {{ this.person.surname }}
            </h2>
          </div>

          <div
            class="md-layout-item md-size-100"
            v-if="showCustomerInformation"
          >
            <div class="md-layout-item md-size-100">
              <md-list class="md-double-line">
                <md-list-item>
                  <md-icon>wc</md-icon>
                  <div class="md-list-item-text">
                    <span>{{ $tc("words.gender") }}</span>
                    <span>{{ this.person.gender || "N/A" }}</span>
                  </div>
                </md-list-item>
                <md-divider></md-divider>
                <md-list-item>
                  <md-icon>school</md-icon>
                  <div class="md-list-item-text">
                    <span>{{ $tc("words.education") }}</span>
                    <span>
                      {{ this.person.education || "N/A" }}
                    </span>
                  </div>
                </md-list-item>
                <md-divider></md-divider>
                <md-list-item>
                  <md-icon>cake</md-icon>
                  <div class="md-list-item-text">
                    <span>{{ $tc("words.birthday") }}</span>
                    <span>
                      {{ this.personService.person.birthDate || "N/A" }}
                    </span>
                  </div>
                </md-list-item>

                <div class="md-layout-item" v-if="person.addresses.length > 0">
                  <md-divider></md-divider>
                  <md-list-item>
                    <md-icon>email</md-icon>
                    <div class="md-list-item-text">
                      <span>{{ $tc("words.email") }}</span>
                      <span>
                        {{ person.addresses[0].email || "N/A" }}
                      </span>
                    </div>
                  </md-list-item>
                  <md-divider></md-divider>
                  <md-list-item>
                    <md-icon>phone</md-icon>
                    <div class="md-list-item-text">
                      <span>{{ $tc("words.phone") }}</span>
                      <span>
                        {{ person.addresses[0].phone || "N/A" }}
                      </span>
                    </div>
                  </md-list-item>
                </div>
              </md-list>
            </div>
          </div>
        </div>
      </md-card-content>
    </md-card>
  </widget>
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
  computed: {
    initials() {
      const person = this.person
      if (!person) return ""

      const first = person.name?.charAt(0) ?? ""
      const last = person.surname?.charAt(0) ?? ""

      return (first + last).toUpperCase()
    },
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
