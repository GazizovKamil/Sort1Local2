using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using Microsoft.AspNetCore;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.FileProviders;
using Microsoft.Extensions.Hosting;

namespace PhpProject.Server
{
    class Program
    {
        static void Main(string[] args)
        {
            var host = WebHost.CreateDefaultBuilder(args)
                .UseStartup<Startup>()
                .UseUrls("http://*:5004/")
                .Build();

            host.Run();
        }
    }

    class Startup
    {
        public void ConfigureServices(IServiceCollection services)
        {
            // Adds a default in-memory implementation of IDistributedCache.
            services.AddDistributedMemoryCache();

            services.AddSession(options =>
            {
                options.IdleTimeout = TimeSpan.FromMinutes(30);
                options.Cookie.HttpOnly = true;
            });

            services.AddPhp(options =>
            {
                //
            });
        }

        public void Configure(IApplicationBuilder app, IHostEnvironment env)
        {
            //// sample usage of URL rewrite:
            //var options = new RewriteOptions()
            //    .AddRewrite(@"^rule/(\w+)", "index.php?word=$1", skipRemainingRules: true);

            //app.UseRewriter(options);

            // enable session:
            app.UseSession();

            // enable .php files from compiled assembly:
            var contentPath = ResolveContentPath();

            app.Use(async (context, next) =>
            {
                if (context.Request.Path == "/")
                {
                    context.Response.Redirect("/index.php");
                    return;
                }

                var path = context.Request.Path.Value;
                if (!string.IsNullOrEmpty(path)
                    && !Path.HasExtension(path)
                    && !path.StartsWith("/index.php", StringComparison.OrdinalIgnoreCase))
                {
                    var relativePath = path.TrimStart('/').Replace('/', Path.DirectorySeparatorChar);
                    var absolutePath = Path.Combine(contentPath, relativePath);
                    var isPhysicalPath = File.Exists(absolutePath) || Directory.Exists(absolutePath);

                    if (!isPhysicalPath)
                    {
                    var queryItems = context.Request.Query
                        .SelectMany(kvp => kvp.Value, (kvp, v) => new KeyValuePair<string, string>(kvp.Key, v))
                        .Where(kvp => !string.Equals(kvp.Key, "q", StringComparison.OrdinalIgnoreCase))
                        .ToList();

                    queryItems.Add(new KeyValuePair<string, string>("q", path));

                    context.Request.Path = "/index.php";
                    context.Request.QueryString = QueryString.Create(queryItems);
                    }
                }

                await next();
            });
            
            app.UsePhp("/", rootPath: contentPath);
            app.UseStaticFiles(new StaticFileOptions { FileProvider = new PhysicalFileProvider(contentPath) });
            
            //
            app.UseDefaultFiles();
            app.UseStaticFiles();
        }

        /// <summary>
        /// Gets location of website project content.
        /// In development, we use the original website project location.
        /// Otherwise, content files are published to the current working directory.
        /// </summary>
        /// <returns></returns>
        static string ResolveContentPath()
        {
            var devcontent = Path.GetFullPath("../Website");
            if (Directory.Exists(devcontent))
            {
                return devcontent;
            }

            return Directory.GetCurrentDirectory();
        }
    }
}