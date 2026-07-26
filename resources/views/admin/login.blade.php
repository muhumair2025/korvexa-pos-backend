<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperAdmin Login — ApexPOS Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl space-y-6">
        
        <div class="text-center space-y-2">
            <div class="w-14 h-14 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-xl flex items-center justify-center font-black text-2xl mx-auto">
                A
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">ApexPOS Platform Login</h1>
            <p class="text-xs text-slate-400">SuperAdmin Control Center for Tenant & License Management</p>
        </div>

        @if($errors->any())
        <div class="p-3 bg-rose-950/80 border border-rose-800 text-rose-300 text-xs rounded-lg">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">SuperAdmin Email</label>
                <input 
                    type="email" 
                    name="email" 
                    value="{{ old('email', 'admin@apexpos.com') }}" 
                    required 
                    autofocus
                    class="w-full px-4 py-3 bg-slate-800/80 border border-slate-700 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    class="w-full px-4 py-3 bg-slate-800/80 border border-slate-700 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-emerald-500">
                    <span>Remember session</span>
                </label>
            </div>

            <button 
                type="submit" 
                class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm rounded-lg shadow-lg transition-all cursor-pointer"
            >
                Login to Platform Console
            </button>
        </form>
    </div>
</body>
</html>
