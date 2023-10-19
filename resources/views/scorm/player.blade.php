<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SCORM 1.2 </title>
    <script src="https://cdn.jsdelivr.net/npm/scorm-again@latest/dist/scorm-again.js"></script>
    <style>
        html,body,iframe { width: 100%; height: 100%; padding: 0; margin: 0; border: none}
        iframe { display:block }
    </style>
    <script type="text/javascript">
        const settings = @json($data);
        const token = settings.token;
        const cmi = settings.player.cmi;

        if (settings.version === 'scorm_12') {
            scorm12();
        }
        else if (settings.version === 'scorm_2004') {
            scorm2004();
        }

        function scorm12() {
            window.API = new Scorm12API(settings.player);
            window.API.loadFromJSON(cmi);

            window.API.on('LMSSetValue.cmi.*', function(CMIElement, value) {
                const data = {
                    cmi: {
                        [CMIElement]: value
                    }
                }
                console.log(data);
                post(data);
            });

            // window.API.on('LMSGetValue.cmi.*', function(CMIElement) {
            //     get(CMIElement)
            //         .then(res => res.json())
            //         .then(res => {
            //             window.API.LMSSetValue(CMIElement, res)
            //         })
            // });

            window.API.on('LMSCommit', function() {
                const data = {
                    cmi: window.API.cmi
                }

                console.log(data);
                post(data);
            });
        }

        function scorm2004() {
            window.API_1484_11 = new Scorm2004API(settings.player);
            window.API_1484_11.loadFromJSON(cmi);

            window.API_1484_11.on('SetValue.cmi.*', function(CMIElement, value) {
                const data = {
                    cmi: {
                        [CMIElement]: value
                    }
                }

                post(data);
            });

            // window.API_1484_11.on('GetValue.cmi.*', function(CMIElement) {
            //     get(CMIElement)
            //         .then(res => res.json())
            //         .then(res => {
            //             window.API_1484_11.SetValue(CMIElement, res)
            //         });
            // });

            window.API_1484_11.on('Commit', function() {
                const data = {
                    cmi: window.API_1484_11.cmi
                }

                post(data);
            });
        }

        function post(data) {
            // if (!token) {
            //     console.log("no token");
            //     return;
            // }

            fetch(settings.lmsUrl, {
                method: 'POST',
                mode: 'cors',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                },

                body: JSON.stringify(data)
            });
            console.log("committed");
        }

        function get(key) {
            if (!token) {
                return;
            }

            return fetch(settings.lmsUrl + '/' + settings.scorm_id + '/' + key, {
                method: 'GET',
                mode: 'cors',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });
        }

    </script>
</head>

<body>
<iframe src={{ $data['entry_url_absolute'] }}></iframe>
{{--<iframe src="{{asset('storage/109fd072-7f03-4090-ab6e-782ce7fc1c4d/index_scorm.html')}}"></iframe>--}}
</body>

</html>
