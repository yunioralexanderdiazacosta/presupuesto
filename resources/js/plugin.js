import { usePage } from '@inertiajs/vue3';
export default {
    install: (app) => {
        app.mixin({
            mounted(){
                let auth = usePage().props.gates;
                let authRoles;
                let authPermissions;
                if(auth !== null){
                    authRoles = usePage().props.gates.roles;
                    authPermissions = usePage().props.gates.permissions;
                    this.$gates.setRoles(authRoles);
                    this.$gates.setPermissions(authPermissions);
                }
            }
        })
    }
}