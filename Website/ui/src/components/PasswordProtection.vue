<template><div></div>
</template>

<script>
export default {
    name: 'PasswordProtection',
    data () {
        return {
            password: '1',
        }
    },
    mounted () {
        this.confirm()
    },
    methods: {
        confirm () {
            this.$swal({
                type: 'question',
                allowOutsideClick: false,
                allowEscapeKey: false,
                title: this.$tc('phrases.passwordProtected'),
                text: this.$tc('phrases.passwordProtected',2),
                inputType: 'password',
                input: 'password',
                inputPlaceholder: this.$tc('words.password'),

                inputValidator: (value) => {
                    //TODO: get the value from config file

                    if (value !== this.password) {
                        this.$swal({
                            type: 'error',
                            text: this.$tc('phrases.wrongPassword'),
                            timer: 1000,
                        }).then(() => {
                            this.$router.replace('/')
                        })

                    }
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
